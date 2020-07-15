topSuite("Ext.layout.container.Center", ['Ext.Panel'], function() {
    var ct, item;

    afterEach(function() {
        item = ct = Ext.destroy(ct);
    });

    function makeCt(cfg) {
        ct = new Ext.container.Container(Ext.apply({
            renderTo: Ext.getBody(),
            defaultType: 'component',
            layout: 'center'
        }, cfg));
        item = ct.items.getAt(0);
    }

    function expectResult(w, h, left, top, ctWidth, ctHeight) {
        var pos = item.getEl().getStyle(['left', 'top']);

        expect(item.getWidth()).toBe(w);
        expect(item.getHeight()).toBe(h);
        expect(parseInt(pos.left, 10)).toBe(left);
        expect(parseInt(pos.top, 10)).toBe(top);
        expect(ct.getWidth()).toBe(ctWidth);
        expect(ct.getHeight()).toBe(ctHeight);
    }

    function makeAutoSizer(w, h) {
        var css = [];

        if (w) {
            css.push('width: ' + w + 'px');
        }

        if (h) {
            css.push('height: ' + h + 'px');
        }

        return '<div style="' + css.join(';') + '"></div>';
    }

    it("should respect bodyPadding when used as a panel", function() {
        var pad = 20;

        ct = new Ext.panel.Panel({
            width: 400,
            height: 400,
            renderTo: Ext.getBody(),
            layout: 'center',
            bodyPadding: pad,
            border: false,
            items: {
                xtype: 'component',
                width: '100%',
                height: '100%'
            }
        });

        item = ct.items.getAt(0);

        expect(item.getX() - ct.getX()).toBe(pad);
        expect(item.getY() - ct.getY()).toBe(pad);
    });

    describe("shrink wrapping child item where dimension is calculated", function() {
        it("should layout width correctly when width is being calculated by parent", function() {
            var p = new Ext.panel.Panel({
                renderTo: Ext.getBody(),
                height: 400,
                width: 400,
                dockedItems: [{
                    xtype: 'container',
                    dock: 'top',
                    layout: 'center',
                    items: [{
                        xtype: 'component',
                        html: makeAutoSizer(100, 30)
                    }]
                }]
            });

            ct = p.getDockedItems()[0];
            item = ct.items.first();
            expectResult(100, 30, 150, 0, 400, 30);
            // Assign here so it gets destroyed.
            ct = p;
        });

        it("should layout height correctly when width is being calculated by parent", function() {
            var p = new Ext.panel.Panel({
                renderTo: Ext.getBody(),
                height: 400,
                width: 400,
                dockedItems: [{
                    xtype: 'container',
                    dock: 'left',
                    layout: 'center',
                    items: [{
                        xtype: 'component',
                        html: makeAutoSizer(30, 100)
                    }]
                }]
            });

            ct = p.getDockedItems()[0];
            item = ct.items.first();
            expectResult(30, 100, 0, 150, 30, 400);
            // Assign here so it gets destroyed.
            ct = p;
        });
    });

    describe("container: fixed width, fixed height", function() {
        function makeSuiteCt(item) {
           makeCt({
                width: 400,
                height: 400,
                items: item
            });
        }

        function expectSuiteResult(w, h, left, top) {
            // Ct size is static
            expectResult(w, h, left, top, 400, 400);
        }

        describe("component: fixed width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 70, 80);
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%'
                    });
                    expectSuiteResult(200, 160, 100, 120);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 144, 70, 128);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '10%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '80%',
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 100, 150);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50)
                    });
                    expectSuiteResult(200, 50, 100, 175);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 50, 70, 155);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 50),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 300),
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 100, 150);
                    });
                });
            });
        });

        describe("component: calculated width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200
                    });
                    expectSuiteResult(320, 200, 40, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 200, 64, 80);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '10%',
                            minWidth: 320,
                            height: 200
                        });
                        expectSuiteResult(320, 200, 40, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            maxWidth: 100,
                            height: 200
                        });
                        expectSuiteResult(100, 200, 150, 100);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%'
                    });
                    expectSuiteResult(320, 160, 40, 120);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 144, 64, 128);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(320, 50, 40, 175);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minWidth: 350
                        });
                        expectSuiteResult(350, 160, 25, 120);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 160, 150, 120);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100)
                    });
                    expectSuiteResult(320, 100, 40, 150);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 100, 64, 130);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 350),
                            maxHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 50);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minWidth: 350
                        });
                        expectSuiteResult(350, 100, 25, 150);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 150, 150);
                    });
                });
            });
        });

        describe("component: auto width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 70, 80);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            minWidth: 300
                        });
                        expectSuiteResult(300, 200, 50, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 200, 150, 100);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%'
                    });
                    expectSuiteResult(200, 160, 100, 120);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 144, 70, 128);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 100, 175);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minWidth: 300
                        });
                        expectSuiteResult(300, 160, 50, 120);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 160, 150, 120);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100)
                    });
                    expectSuiteResult(200, 100, 100, 150);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 100, 70, 130);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 50);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 100, 175);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minWidth: 300
                        });
                        expectSuiteResult(300, 100, 50, 150);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 150, 150);
                    });
                });
            });
        });
     });

    describe("container: fixed width, auto height", function() {
        function makeSuiteCt(item) {
            makeCt({
                width: 400,
                items: item
            });
        }

        function expectSuiteResult(w, h, left, ctHeight) {
            // Top of the item & ctWidth are static
            expectResult(w, h, left, 0, 400, ctHeight);
        }

        describe("component: fixed width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 70, 240);
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%'
                    });
                    expectSuiteResult(200, 0, 100, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 0, 70, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '10%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '80%',
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 0, 100, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50)
                    });
                    expectSuiteResult(200, 50, 100, 50);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 50, 70, 90);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 50),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 300),
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 100, 100);
                    });
                });
            });
        });

        describe("component: calculated width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200
                    });
                    expectSuiteResult(320, 200, 40, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 200, 64, 240);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '10%',
                            minWidth: 320,
                            height: 200
                        });
                        expectSuiteResult(320, 200, 40, 200);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            maxWidth: 100,
                            height: 200
                        });
                        expectSuiteResult(100, 200, 150, 200);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%'
                    });
                    expectSuiteResult(320, 0, 40, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 0, 64, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(320, 0, 40, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minWidth: 350
                        });
                        expectSuiteResult(350, 0, 25, 0);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 0, 150, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100)
                    });
                    expectSuiteResult(320, 100, 40, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(272, 100, 64, 140);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 350),
                            maxHeight: 300
                        });
                        expectSuiteResult(320, 300, 40, 300);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minWidth: 350
                        });
                        expectSuiteResult(350, 100, 25, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 150, 100);
                    });
                });
            });
        });

        describe("component: auto width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 70, 240);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            minWidth: 300
                        });
                        expectSuiteResult(300, 200, 50, 200);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 200, 150, 200);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%'
                    });
                    expectSuiteResult(200, 0, 100, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 0, 70, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 0, 100, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minWidth: 300
                        });
                        expectSuiteResult(300, 0, 50, 0);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 0, 150, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100)
                    });
                    expectSuiteResult(200, 100, 100, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 100, 70, 140);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 100, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 100, 50);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minWidth: 300
                        });
                        expectSuiteResult(300, 100, 50, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 150, 100);
                    });
                });
            });
        });
    });

    describe("container: auto width, fixed height", function() {
        function makeSuiteCt(item) {
            makeCt({
                floating: true, // Float the ct so it shrink wraps
                height: 400,
                items: item
            });
        }

        function expectSuiteResult(w, h, top, ctWidth) {
            // Left of the item & ctHeight are static
            expectResult(w, h, 0, top, ctWidth, 400);
        }

        describe("component: fixed width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 80, 260);
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%'
                    });
                    expectSuiteResult(200, 160, 120, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 144, 128, 260);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '10%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 50, 200);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '80%',
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 150, 200);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50)
                    });
                    expectSuiteResult(200, 50, 175, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 50, 155, 260);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 50),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 50, 200);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 300),
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 150, 200);
                    });
                });
            });
        });

        describe("component: calculated width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200
                    });
                    expectSuiteResult(0, 200, 100, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 200, 80, 60);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '10%',
                            minWidth: 320,
                            height: 200
                        });
                        expectSuiteResult(320, 200, 100, 320);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            maxWidth: 100,
                            height: 200
                        });
                        expectSuiteResult(0, 200, 100, 0);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%'
                    });
                    expectSuiteResult(0, 160, 120, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 144, 128, 60);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(0, 300, 50, 0);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(0, 50, 175, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minWidth: 350
                        });
                        expectSuiteResult(350, 160, 120, 350);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(0, 160, 120, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100)
                    });
                    expectSuiteResult(0, 100, 150, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 100, 130, 60);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(0, 300, 50, 0);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 350),
                            maxHeight: 300
                        });
                        expectSuiteResult(0, 300, 50, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minWidth: 350
                        });
                        expectSuiteResult(350, 100, 150, 350);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(0, 100, 150, 0);
                    });
                });
            });
        });

        describe("component: auto width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200
                    });
                    expectSuiteResult(200, 200, 100, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 80, 260);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            minWidth: 300
                        });
                        expectSuiteResult(300, 200, 100, 300);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 200, 100, 100);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%'
                    });
                    expectSuiteResult(200, 160, 120, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 144, 128, 260);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 50, 200);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 175, 200);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minWidth: 300
                        });
                        expectSuiteResult(300, 160, 120, 300);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 160, 120, 100);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100)
                    });
                    expectSuiteResult(200, 100, 150, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 100, 130, 260);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 50, 200);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 175, 200);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minWidth: 300
                        });
                        expectSuiteResult(300, 100, 150, 300);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 150, 100);
                    });
                });
            });
        });
    });

    describe("container: auto width, auto height", function() {
        function makeSuiteCt(item) {
            makeCt({
                floating: true, // Float the ct so it shrink wraps
                items: item
            });
        }

        function expectSuiteResult(w, h, ctWidth, ctHeight) {
            // Position of the item should always be 0,0
            expectResult(w, h, 0, 0, ctWidth, ctHeight);
        }

        describe("component: fixed width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200
                    });
                    expectSuiteResult(200, 200, 200, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 260, 240);
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%'
                    });
                    expectSuiteResult(200, 0, 200, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 0, 260, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '10%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 200, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            height: '80%',
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 0, 200, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50)
                    });
                    expectSuiteResult(200, 50, 200, 50);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: 200,
                        html: makeAutoSizer(null, 50),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 50, 260, 90);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 50),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 200, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: 200,
                            html: makeAutoSizer(null, 300),
                            maxHeight: 100
                        });
                        expectSuiteResult(200, 100, 200, 100);
                    });
                });
            });
        });

        describe("component: calculated width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200
                    });
                    expectSuiteResult(0, 200, 0, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 200, 60, 240);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '10%',
                            minWidth: 320,
                            height: 200
                        });
                        expectSuiteResult(320, 200, 320, 200);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            maxWidth: 100,
                            height: 200
                        });
                        expectSuiteResult(0, 200, 0, 200);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%'
                    });
                    expectSuiteResult(0, 0, 0, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 0, 60, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(0, 300, 0, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(0, 0, 0, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            minWidth: 350
                        });
                        expectSuiteResult(350, 0, 350, 0);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(0, 0, 0, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100)
                    });
                    expectSuiteResult(0, 100, 0, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        width: '80%',
                        html: makeAutoSizer(null, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(0, 100, 60, 140);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(0, 300, 0, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 350),
                            maxHeight: 300
                        });
                        expectSuiteResult(0, 300, 0, 300);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            minWidth: 350
                        });
                        expectSuiteResult(350, 100, 350, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            width: '80%',
                            html: makeAutoSizer(null, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(0, 100, 0, 100);
                    });
                });
            });
        });

        describe("component: auto width", function() {
            describe("fixed height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200
                    });
                    expectSuiteResult(200, 200, 200, 200);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: 200,
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 200, 260, 240);
                });

                describe("constraints", function() {
                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            minWidth: 300
                        });
                        expectSuiteResult(300, 200, 300, 200);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: 200,
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 200, 100, 200);
                    });
                });
            });

            describe("calculated height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%'
                    });
                    expectSuiteResult(200, 0, 200, 0);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200),
                        height: '40%',
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 0, 260, 40);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 200, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 0, 200, 0);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            minWidth: 300
                        });
                        expectSuiteResult(300, 0, 300, 0);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200),
                            height: '40%',
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 0, 100, 0);
                    });
                });
            });

            describe("auto height", function() {
                it("should center the item", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100)
                    });
                    expectSuiteResult(200, 100, 200, 100);
                });

                it("should take margins into account", function() {
                    makeSuiteCt({
                        html: makeAutoSizer(200, 100),
                        margin: '20 30'
                    });
                    expectSuiteResult(200, 100, 260, 140);
                });

                describe("constraints", function() {
                    it("should take minHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minHeight: 300
                        });
                        expectSuiteResult(200, 300, 200, 300);
                    });

                    it("should take maxHeight into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxHeight: 50
                        });
                        expectSuiteResult(200, 50, 200, 50);
                    });

                    it("should take minWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            minWidth: 300
                        });
                        expectSuiteResult(300, 100, 300, 100);
                    });

                    it("should take maxWidth into account", function() {
                        makeSuiteCt({
                            html: makeAutoSizer(200, 100),
                            maxWidth: 100
                        });
                        expectSuiteResult(100, 100, 100, 100);
                    });
                });
            });
        });
    });

});
