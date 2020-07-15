topSuite("Ext.layout.component.Dock", ['Ext.Panel', 'Ext.Button'], function() {
    var ct;

    afterEach(function() {
        if (ct && !ct.$protected) {
            Ext.destroy(ct);
            ct = null;
        }
    });

    function makeCt(options, layoutOptions) {
        var failedLayouts = Ext.failedLayouts;

        ct = Ext.widget(Ext.apply({
                xtype: 'panel',
                renderTo: Ext.getBody()
            }, options));

        // eslint-disable-next-line eqeqeq
        if (failedLayouts != Ext.failedLayouts) {
            expect('failedLayout=true').toBe('false');
        }
    }

    describe("shrink wrapping around docked items", function() {
        var top = 'top',
            left = 'left',
            u; // u to be used as undefined

        var makeDocked = function(dock, w, h, html) {
            var style = {};

            if (w) {
                style.width = w + 'px';
            }

            if (h) {
                style.height = h + 'px';
            }

            return new Ext.Component({
                dock: dock,
                shrinkWrap: true,
                style: style,
                html: html
            });
        };

        describe("width", function() {
            var makeDocker = function(options) {
                return makeCt(Ext.apply({
                    shrinkWrap: true,
                    border: false,
                    bodyBorder: false,
                    shrinkWrapDock: 2
                }, options));
            };

            it("should stretch the body width if the docked item is larger", function() {
                makeDocker({
                    dockedItems: [
                         makeDocked(top, 100, u)
                    ],
                    html: 'a'
                });
                expect(ct.getWidth()).toBe(100);
                expect(ct.body.getWidth()).toBe(100);
            });

            it("should stretch the docked width if the body is larger", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, u, u, 'a')
                    ],
                    html: '<div style="width: 100px;"></div>'
                });
                expect(ct.getWidth()).toBe(100);
                expect(ct.getDockedItems()[0].getWidth()).toBe(100);
            });

            it("should stretch other docked items to the size of the largest docked item if it is bigger than the body", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 100, u),
                        makeDocked(top, u, u, 'b')
                    ],
                    html: 'a'
                });
                expect(ct.getDockedItems()[1].getWidth()).toBe(100);
            });

            it("should stretch all docked items to the size of the body if the body is largest", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, u, u, 'a'),
                        makeDocked(top, u, u, 'b')
                    ],
                    html: '<div style="width: 100px;"></div>'
                });
                expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                expect(ct.getDockedItems()[1].getWidth()).toBe(100);
            });

            it("should stretch all items if the body and a single docked item are the largest & same size", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 50, u, u),
                        makeDocked(top, 100, u, u)
                    ],
                    html: '<div style="width: 100px;"></div>'
                });
                expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                expect(ct.getDockedItems()[1].getWidth()).toBe(100);
            });
        });

        describe("height", function() {
            var makeDocker = function(options) {
                return makeCt(Ext.apply({
                    shrinkWrap: true,
                    border: false,
                    bodyBorder: false,
                    shrinkWrapDock: 1
                }, options));
            };

            it("should stretch the body height if the docked item is larger", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(left, u, 100)
                    ],
                    html: 'a'
                });
                expect(ct.getHeight()).toBe(100);
                expect(ct.body.getHeight()).toBe(100);
            });

            it("should stretch the docked height if the body is larger", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(left, u, u, 'a')
                    ],
                    html: '<div style="height: 100px;"></div>'
                });
                expect(ct.getHeight()).toBe(100);
                expect(ct.getDockedItems()[0].getHeight()).toBe(100);
            });

            it("should stretch other docked items to the size of the largest docked item if it is bigger than the body", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(left, u, 100),
                        makeDocked(left, u, u, 'b')
                    ],
                    html: 'a'
                });
                expect(ct.getDockedItems()[1].getHeight()).toBe(100);
            });

            it("should stretch all docked items to the size of the body if the body is largest", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(left, u, u, 'a'),
                        makeDocked(left, u, u, 'b')
                    ],
                    html: '<div style="height: 100px;"></div>'
                });
                expect(ct.getDockedItems()[0].getHeight()).toBe(100);
                expect(ct.getDockedItems()[1].getHeight()).toBe(100);
            });

            it("should stretch all items if the body and a single docked item are the largest & same size", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(left, u, 50, u),
                        makeDocked(left, u, 100, u)
                    ],
                    html: '<div style="height: 100px;"></div>'
                });
                expect(ct.getDockedItems()[0].getHeight()).toBe(100);
                expect(ct.getDockedItems()[1].getHeight()).toBe(100);
            });
        });

        describe("combination", function() {
            var makeDocker = function(options) {
                return makeCt(Ext.apply({
                    shrinkWrap: true,
                    border: false,
                    bodyBorder: false,
                    shrinkWrapDock: true
                }, options));
            };

            it("should stretch the body in both dimensions if the docked items are larger", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 100, u),
                        makeDocked(left, u, 75)
                    ],
                    html: 'a'
                });
                expect(ct.getWidth()).toBe(100);
                expect(ct.body.getWidth()).toBe(100);
                expect(ct.getHeight()).toBe(75);
                expect(ct.body.getHeight()).toBe(75);
            });

            it("should only stretch the width the dimension where the body is smaller", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 100, u),
                        makeDocked(left, u, 75)
                    ],
                    html: '<div style="width: 50px; height: 100px;">'
                });
                expect(ct.getWidth()).toBe(100);
                expect(ct.body.getWidth()).toBe(100);
                expect(ct.getHeight()).toBe(100);
                expect(ct.body.getHeight()).toBe(100);
                expect(ct.getDockedItems()[1].getHeight()).toBe(100);
            });

            it("should only stretch the height the dimension where the body is smaller", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 100, u),
                        makeDocked(left, u, 75)
                    ],
                    html: '<div style="width: 200px; height: 50px;">'
                });
                expect(ct.getHeight()).toBe(75);
                expect(ct.body.getHeight()).toBe(75);
                expect(ct.getWidth()).toBe(200);
                expect(ct.body.getWidth()).toBe(200);
                expect(ct.getDockedItems()[0].getWidth()).toBe(200);
            });

            it("should not stretch the body if neither docked item is bigger", function() {
                makeDocker({
                    dockedItems: [
                        makeDocked(top, 100, u),
                        makeDocked(left, u, 75)
                    ],
                    html: '<div style="width: 200px; height: 100px;">'
                });
                expect(ct.getWidth()).toBe(200);
                expect(ct.body.getWidth()).toBe(200);
                expect(ct.getHeight()).toBe(100);
                expect(ct.body.getHeight()).toBe(100);
                expect(ct.getDockedItems()[0].getWidth()).toBe(200);
                expect(ct.getDockedItems()[1].getHeight()).toBe(100);
            });
        });

        describe("min/max constraints", function() {
            describe("width", function() {
                var makeDocker = function(options) {
                    return makeCt(Ext.apply({
                        shrinkWrap: true,
                        border: false,
                        bodyBorder: false,
                        shrinkWrapDock: 2
                    }, options));
                };

                it("should constrain to a minWidth", function() {
                    makeDocker({
                        minWidth: 200,
                        dockedItems: [
                            makeDocked(top, 100, u)
                        ]
                    });
                    expect(ct.getWidth()).toBe(200);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(200);
                });

                it("should constrain to a maxWidth", function() {
                    makeDocker({
                        maxWidth: 100,
                        dockedItems: [
                            makeDocked(top, 200, u)
                        ]
                    });
                    expect(ct.getWidth()).toBe(100);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                });
            });

            describe("height", function() {
                var makeDocker = function(options) {
                    return makeCt(Ext.apply({
                        shrinkWrap: true,
                        border: false,
                        bodyBorder: false,
                        shrinkWrapDock: 1
                    }, options));
                };

                it("should constrain to a minHeight", function() {
                    makeDocker({
                        minHeight: 200,
                        dockedItems: [
                            makeDocked(left, u, 100)
                        ]
                    });
                    expect(ct.getHeight()).toBe(200);
                    expect(ct.getDockedItems()[0].getHeight()).toBe(200);
                });

                it("should constrain to a maxWidth", function() {
                    makeDocker({
                        maxHeight: 100,
                        dockedItems: [
                            makeDocked(left, u, 200)
                        ]
                    });
                    expect(ct.getHeight()).toBe(100);
                    expect(ct.getDockedItems()[0].getHeight()).toBe(100);
                });
            });

            describe("combination", function() {
                var makeDocker = function(options) {
                    return makeCt(Ext.apply({
                        shrinkWrap: true,
                        border: false,
                        bodyBorder: false,
                        shrinkWrapDock: true
                    }, options));
                };

                it("should constrain a minHeight & maxWidth", function() {
                    makeDocker({
                        minHeight: 100,
                        maxWidth: 100,
                        dockedItems: [
                            makeDocked(top, 200, u),
                            makeDocked(left, u, 50)
                        ]
                    });
                    expect(ct.getWidth()).toBe(100);
                    expect(ct.getHeight()).toBe(100);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                    expect(ct.getDockedItems()[1].getHeight()).toBe(100);
                });

                it("should constrain a maxHeight & minWidth", function() {
                    makeDocker({
                        maxHeight: 100,
                        minWidth: 100,
                        dockedItems: [
                            makeDocked(top, 50, u),
                            makeDocked(left, u, 200)
                        ]
                    });
                    expect(ct.getWidth()).toBe(100);
                    expect(ct.getHeight()).toBe(100);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                    expect(ct.getDockedItems()[1].getHeight()).toBe(100);
                });

                it("should constrain a minHeight and minWidth", function() {
                    makeDocker({
                        minHeight: 100,
                        minWidth: 100,
                        dockedItems: [
                            makeDocked(top, 50, u),
                            makeDocked(left, u, 50)
                        ]
                    });
                    expect(ct.getWidth()).toBe(100);
                    expect(ct.getHeight()).toBe(100);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                    expect(ct.getDockedItems()[1].getHeight()).toBe(100);
                });

                it("should constrain a maxHeight and maxWidth", function() {
                    makeDocker({
                        maxHeight: 100,
                        maxWidth: 100,
                        dockedItems: [
                            makeDocked(top, 200, u),
                            makeDocked(left, u, 200)
                        ]
                    });
                    expect(ct.getWidth()).toBe(100);
                    expect(ct.getHeight()).toBe(100);
                    expect(ct.getDockedItems()[0].getWidth()).toBe(100);
                    expect(ct.getDockedItems()[1].getHeight()).toBe(100);
                });
            });
        });

    });

    describe('interaction within box layout', function() {
        it('should handle stretchmax and minHeight together', function() {
            makeCt({
                    width: 100,
                    border: false,
                    layout: {
                        type: 'hbox',
                        align: 'stretchmax'
                    },
                    items: [{
                        xtype: 'panel',
                        border: false,
                        items: {
                            xtype: 'component',
                            width: 20,
                            height: 20,
                            style: 'background-color: red'
                        },
                        dockedItems: [{
                            xtype: 'component',
                            height: 20,
                            dock: 'bottom',
                            style: 'background-color: blue'
                        }],
                        minHeight: 100
                    }, {
                        xtype: 'component',
                        style: 'background-color: yellow',
                        height: 200,
                        width: 20
                    }]
                });

            expect(ct).toHaveLayout({
                el: { w: 100, h: 200 },
                items: {
                    0: {
                        el: { xywh: '0 0 20 200' },
                        items: {
                            0: { el: { xywh: '0 0 20 20' } }
                        },
                        dockedItems: {
                            0: { el: { xywh: '0 180 20 20' } }
                        }
                    },
                    1: { el: { xywh: '20 0 20 200' } }
                }
            });
        });
    });

    describe("DOM element order", function() {
        var defaultDockedItems = [{
            dock: 'top',
            weight: 0,
            xtype: 'toolbar',
            items: [{
                xtype: 'component',
                html: 'top outer'
            }]
        }, {
            dock: 'left',
            weight: 0,
            xtype: 'toolbar',
            vertical: true,
            items: [{
                xtype: 'component',
                html: 'left outer'
            }]
        }, {
            dock: 'bottom',
            weight: 0,
            xtype: 'toolbar',
            items: [{
                xtype: 'component',
                html: 'bottom outer'
            }]
        }, {
            dock: 'right',
            weight: 0,
            xtype: 'toolbar',
            vertical: true,
            items: [{
                xtype: 'component',
                html: 'right outer'
            }]
        }, {
            xtype: 'toolbar',
            dock: 'top',
            weight: 10,
            items: [{
                xtype: 'component',
                html: 'top inner'
            }]
        }, {
            dock: 'bottom',
            weight: 10,
            xtype: 'toolbar',
            items: [{
                xtype: 'component',
                html: 'bottom inner'
            }]
        }, {
            dock: 'right',
            weight: 10,
            xtype: 'toolbar',
            vertical: true,
            items: [{
                xtype: 'component',
                html: 'right inner'
            }]
        }, {
            dock: 'left',
            weight: 10,
            xtype: 'toolbar',
            vertical: true,
            items: [{
                xtype: 'component',
                html: 'left inner'
            }]
        }];

        function makeSuite(config, elOrder, wrapOrder, changeFn, desc) {
            config = config || {};
            elOrder = elOrder || [];
            wrapOrder = wrapOrder || [];
            desc = desc ? desc + ',' : 'panel w/';

            var hasHeader = config.title === null ? false : true;

            var numElChildren = elOrder.length;

            var numWrapChildren = wrapOrder.length;

            var suiteDesc = desc +
                            ((config.title === null ? ' no header' : ' header position: ') +
                            (config.headerPosition || 'left')) +
                            (config.dockedItems === null ? ' no dockedItems' : ' w/ dockedItems') +
                            ', frame: ' + !!config.frame +
                            ', tab guards: ' + (config.tabGuard ? 'on' : 'off');

            function countChicks(panel, property, expected) {
                var numExpected = expected.length,
                    children = panel[property].dom.childNodes,
                    child, want, i;

                for (i = 0; i < numExpected; i++) {
                    child = children[i];

                    if (child) {
                        want = expected[i];

                        // Number is docked.getAt(x), string is element property name
                        if (typeof want === 'number') {
                            want = panel.dockedItems.getAt(want);
                        }
                        else {
                            want = panel[want];
                        }

                        expect(child.id).toBe(want.id);
                    }
                    else {
                        fail("DOM child not found at index " + i);
                    }
                }
            }

            describe(suiteDesc, function() {
                beforeAll(function() {
                    var cfg = Ext.apply({
                        margin: 20,
                        width: 400,
                        height: 300,
                        collapsible: hasHeader ? true : false,
                        animCollapse: false,

                        title: 'blerg',

                        dockedItems: defaultDockedItems,
                        html: 'zingbong'
                    }, config);

                    makeCt(cfg);

                    ct.$protected = true;

                    if (changeFn) {
                        changeFn(ct);
                    }
                });

                afterAll(function() {
                    ct = Ext.destroy(ct);
                });

                it("should have " + numElChildren + " children in main el", function() {
                    expect(ct.el.dom.childNodes.length).toBe(numElChildren);
                });

                it("should have main el children in right order", function() {
                    countChicks(ct, 'el', elOrder);
                });

                it("should have " + numWrapChildren + " children in bodyWrap el", function() {
                    expect(ct.bodyWrap.dom.childNodes.length).toBe(numWrapChildren);
                });

                it("should have bodyWrap el children in right order", function() {
                    countChicks(ct, 'bodyWrap', wrapOrder);
                });

                if (hasHeader) {
                    describe("collapsed", function() {
                        beforeAll(function() {
                            ct.collapse();
                        });

                        it("should have " + numElChildren + " children in main el", function() {
                            expect(ct.el.dom.childNodes.length).toBe(numElChildren);
                        });

                        it("should have main el children in right order", function() {
                            countChicks(ct, 'el', elOrder);
                        });

                        it("should have " + numWrapChildren + " children in bodyWrap el", function() {
                            expect(ct.bodyWrap.dom.childNodes.length).toBe(numWrapChildren);
                        });

                        it("should have bodyWrap el children in right order", function() {
                            countChicks(ct, 'bodyWrap', wrapOrder);
                        });

                        describe("expanded", function() {
                            beforeAll(function() {
                                ct.expand();
                            });

                            it("should have " + numElChildren + " children in main el", function() {
                                expect(ct.el.dom.childNodes.length).toBe(numElChildren);
                            });

                            it("should have main el children in right order", function() {
                                countChicks(ct, 'el', elOrder);
                            });

                            it("should have " + numWrapChildren + " children in bodyWrap el", function() {
                                expect(ct.bodyWrap.dom.childNodes.length).toBe(numWrapChildren);
                            });

                            it("should have bodyWrap el children in right order", function() {
                                countChicks(ct, 'bodyWrap', wrapOrder);
                            });
                        });
                    });
                }
            });
        }

        // Com-pre-hen-sive is the code word for today

        function addHeader(panel, title) {
            panel.setTitle(title || 'foobork');
        }

        function addItems(panel, items) {
            items = items || defaultDockedItems;

            for (var i = 0, len = items.length; i < len; i++) {
                panel.addDocked(items[i]);
            }
        }

        function addHeaderAndItems(panel) {
            addItems(panel);
            addHeader(panel);
        }

        // No header
        makeSuite({ title: null, dockedItems: null }, ['bodyWrap'], ['body']);
        makeSuite({ title: null, dockedItems: null, tabGuard: true },
            ['tabGuardBeforeEl', 'bodyWrap', 'tabGuardAfterEl'], ['body']);

        // No header but massive eruption of dockedItems
        makeSuite({ title: null }, ['bodyWrap'], [0, 1, 4, 7, 'body', 3, 2, 5, 6]);

        // No header, dockedItems plus tabGuards
        makeSuite({ title: null, tabGuard: true },
            ['tabGuardBeforeEl', 'bodyWrap', 'tabGuardAfterEl'],
            [0, 1, 4, 7, 'body', 3, 2, 5, 6]);

        // Header position
        makeSuite({ dockedItems: null, headerPosition: 'top' }, [0, 'bodyWrap'], ['body']);
        makeSuite({ dockedItems: null, headerPosition: 'left' }, [0, 'bodyWrap'], ['body']);
        makeSuite({ dockedItems: null, headerPosition: 'right' }, ['bodyWrap', 0], ['body']);
        makeSuite({ dockedItems: null, headerPosition: 'bottom' }, ['bodyWrap', 0], ['body']);

        // Header position *and* dockedItems
        makeSuite({ headerPosition: 'top' }, [0, 'bodyWrap'], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ headerPosition: 'left' }, [0, 'bodyWrap'], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ headerPosition: 'right' }, ['bodyWrap', 0], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ headerPosition: 'bottom' }, ['bodyWrap', 0], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);

        // Header position with tab guards
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'], ['body']);
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'], ['body']);
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'], ['body']);
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'], ['body']);

        // Header position with tab guards and dockedItems
        makeSuite({ tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
        makeSuite({ tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7]);

        // Header added after rendering
        makeSuite({ dockedItems: null, title: null, headerPosition: 'top' },
            [0, 'bodyWrap'], ['body'], addHeader, 'dynamic header 1');
        makeSuite({ dockedItems: null, title: null, headerPosition: 'left' },
            [0, 'bodyWrap'], ['body'], addHeader, 'dynamic header 2');
        makeSuite({ dockedItems: null, title: null, headerPosition: 'right' },
            ['bodyWrap', 0], ['body'], addHeader, 'dynamic header 3');
        makeSuite({ dockedItems: null, title: null, headerPosition: 'bottom' },
            ['bodyWrap', 0], ['body'], addHeader, 'dynamic header 4');

        // Header added after rendering onto existing tab guards
        makeSuite({ dockedItems: null, title: null, tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'], ['body'], addHeader,
            'dynamic header 5');
        makeSuite({ dockedItems: null, title: null, tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'], ['body'], addHeader,
            'dynamic header 6');
        makeSuite({ dockedItems: null, title: null, tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'], ['body'], addHeader,
            'dynamic header 7');
        makeSuite({ dockedItems: null, title: null, tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'], ['body'], addHeader,
            'dynamic header 8');

        // Header added after rendering onto existing tab guards and dockedItems
        makeSuite({ title: null, tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 9');
        makeSuite({ title: null, tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 10');
        makeSuite({ title: null, tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 11');
        makeSuite({ title: null, tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 12');

        // Finally, dynamically added dockedItems. One by one. Ha!
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 1');
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 2');
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 3');
        makeSuite({ dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 4');

        // All together now.
        makeSuite({ title: null, dockedItems: null, tabGuard: true, headerPosition: 'top' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 5');
        makeSuite({ title: null, dockedItems: null, tabGuard: true, headerPosition: 'left' },
            ['tabGuardBeforeEl', 0, 'bodyWrap', 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 6');
        makeSuite({ title: null, dockedItems: null, tabGuard: true, headerPosition: 'right' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 7');
        makeSuite({ title: null, dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
            ['tabGuardBeforeEl', 'bodyWrap', 0, 'tabGuardAfterEl'],
            [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 8');

        // IE8 gets framed
        if (!Ext.supports.CSS3BorderRadius) {
            // No header
            makeSuite({ frame: true, title: null, dockedItems: null },
                ['frameTL', 'frameML', 'frameBL'], ['body']);
            makeSuite({ frame: true, title: null, dockedItems: null, tabGuard: true },
                ['tabGuardBeforeEl', 'frameTL', 'frameML', 'frameBL', 'tabGuardAfterEl'],
                ['body']);

            // No header but massive eruption of dockedItems
            makeSuite({ frame: true, title: null }, ['frameTL', 'frameML', 'frameBL'],
                [0, 1, 4, 7, 'body', 3, 2, 5, 6]);

            // No header, dockedItems plus tabGuards
            makeSuite({ frame: true, title: null, tabGuard: true },
                ['tabGuardBeforeEl', 'frameTL', 'frameML', 'frameBL', 'tabGuardAfterEl'],
                [0, 1, 4, 7, 'body', 3, 2, 5, 6]);

            // bodyContainer === frameML after first layout with docked items present

            // Header position
            makeSuite({ frame: true, dockedItems: null, headerPosition: 'top' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], ['body']);
            makeSuite({ frame: true, dockedItems: null, headerPosition: 'left' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], ['body']);
            makeSuite({ frame: true, dockedItems: null, headerPosition: 'right' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], ['body']);
            makeSuite({ frame: true, dockedItems: null, headerPosition: 'bottom' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], ['body']);

            // Header position *and* dockedItems
            makeSuite({ frame: true, headerPosition: 'top' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, headerPosition: 'left' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, headerPosition: 'right' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, headerPosition: 'bottom' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], [1, 2, 5, 8, 'body', 4, 3, 6, 7]);

            // Header position with tab guards
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                ['body']);
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                ['body']);
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                ['body']);
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                ['body']);

            // Header position with tab guards and dockedItems
            makeSuite({ frame: true, tabGuard: true, headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, tabGuard: true, headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7]);
            makeSuite({ frame: true, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7]);

            // Header added after rendering
            makeSuite({ frame: true, dockedItems: null, title: null, headerPosition: 'top' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], ['body'], addHeader, 'dynamic header 1');
            makeSuite({ frame: true, dockedItems: null, title: null, headerPosition: 'left' },
                [0, 'frameTL', 'bodyContainer', 'frameBL'], ['body'], addHeader, 'dynamic header 2');
            makeSuite({ frame: true, dockedItems: null, title: null, headerPosition: 'right' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], ['body'], addHeader, 'dynamic header 3');
            makeSuite({ frame: true, dockedItems: null, title: null, headerPosition: 'bottom' },
                ['frameTL', 'bodyContainer', 'frameBL', 0], ['body'], addHeader, 'dynamic header 4');

            // Header added after rendering onto existing tab guards
            makeSuite({ frame: true, dockedItems: null, title: null, tabGuard: true,
                        headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                ['body'], addHeader, 'dynamic header 5');
            makeSuite({ frame: true, dockedItems: null, title: null, tabGuard: true,
                headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                ['body'], addHeader, 'dynamic header 6');
            makeSuite({ frame: true, dockedItems: null, title: null, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                ['body'], addHeader, 'dynamic header 7');
            makeSuite({ frame: true, dockedItems: null, title: null, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                ['body'], addHeader, 'dynamic header 8');

            // Header added after rendering onto existing tab guards and dockedItems
            makeSuite({ frame: true, title: null, tabGuard: true, headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 9');
            makeSuite({ frame: true, title: null, tabGuard: true, headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 10');
            makeSuite({ frame: true, title: null, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 11');
            makeSuite({ frame: true, title: null, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeader, 'dynamic header 12');

            // Finally, dynamically added dockedItems. One by one. Ha!
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 1');
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 2');
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 3');
            makeSuite({ frame: true, dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addItems, 'dynamic items 4');

            // All together now.
            makeSuite({ frame: true, title: null, dockedItems: null, tabGuard: true, headerPosition: 'top' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 5');
            makeSuite({ frame: true, title: null, dockedItems: null, tabGuard: true, headerPosition: 'left' },
                ['tabGuardBeforeEl', 0, 'frameTL', 'bodyContainer', 'frameBL', 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 6');
            makeSuite({ frame: true, title: null, dockedItems: null, tabGuard: true, headerPosition: 'right' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 7');
            makeSuite({ frame: true, title: null, dockedItems: null, tabGuard: true, headerPosition: 'bottom' },
                ['tabGuardBeforeEl', 'frameTL', 'bodyContainer', 'frameBL', 0, 'tabGuardAfterEl'],
                [1, 2, 5, 8, 'body', 4, 3, 6, 7], addHeaderAndItems, 'dynamic items 8');
        }
    });

    describe('isValidParent', function() {
        var panel;

        beforeEach(function() {
            panel = new Ext.panel.Panel({
                title: 'Test',
                tbar: {
                    itemId: 'top-toolbar',
                    items: [{
                        text: 'Top Button'
                    }]
                },
                bbar: {
                    itemId: 'bottom-toolbar',
                    items: [{
                        text: 'Bottom Button'
                    }]
                },
                height: 100,
                width: 100,
                renderTo: document.body
            });
        });
        afterEach(function() {
            panel.destroy();
        });

        it('should not find that isValidParent returns false during a layout when docked items use itemId', function() {
            spyOn(panel.componentLayout, 'isValidParent').andCallThrough();

            panel.updateLayout();
            var calls = panel.componentLayout.isValidParent.calls,
                len = calls.length,
                i;

            // All the DockLayout's isValidParent calls during the layout must have returned true
            for (i = 0; i < len; i++) {
                expect(calls[i].result).toBe(true);
            }
        });
    });
});
