topSuite("Ext.layout.container.Anchor",
    ['Ext.form.Panel', 'Ext.form.field.*'],
function() {
    function createSuite(shrinkWrap) {
        var suiteName = 'Ext.layout.container.Anchor';

        if (shrinkWrap) {
            suiteName += ' (shrinkWrap:true)';
        }

        describe(suiteName, function() {
            var longText = 'Lorem ipsum dolor sit amet',
                shortText = 'Lorem ipsum',
                longWord = 'supercalifragilisticexpialidocious',
                scrollbarSize = Ext.getScrollbarSize(),
                scrollbarWidth = scrollbarSize.width,
                scrollbarHeight = scrollbarSize.height,
                panel;

            function makePanel(parentConfig, childConfig) {
                var items = [];

                if (!Ext.isArray(childConfig)) {
                    childConfig = [childConfig];
                }

                Ext.each(childConfig, function(config) {
                    items.push(Ext.apply({
                        xtype: 'component',
                        style: 'margin: 4px; line-height: 20px;'
                    }, config));
                });

                panel = Ext.widget(Ext.apply({
                    renderTo: document.body,
                    xtype: 'panel',
                    shrinkWrap: shrinkWrap || 2,
                    layout: 'anchor',
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

                describe("anchoring items using percentages", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { anchor: '100%, 50%' },
                            { anchor: '50%, 50%' }
                        ]);
                    });

                    it("should width the items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                        expect(panel.items.getAt(1).getWidth()).toBe(35);
                    });

                    // TODO: this spec will need updating when https://sencha.jira.com/browse/EXTJSIV-5892 is fixed
                    // heights should be 38 if the collapsing margins are accounted for.
                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(35);
                        expect(panel.items.getAt(1).getHeight()).toBe(35);
                    });
                });

                describe("anchoring items using offsets", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            // TODO: should we be able to use bottom anchors insead of using a fixed height?
                            // see https://sencha.jira.com/browse/EXTJSIV-5893
                            { anchor: '0', height: 37 },
                            { anchor: '-43', height: 37 }
                        ]);
                    });

                    it("should width the items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                        expect(panel.items.getAt(1).getWidth()).toBe(35);
                    });

                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(37);
                        expect(panel.items.getAt(1).getHeight()).toBe(37);
                    });
                });

                describe("naturally widthed child with long text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: longText });
                    });

                    it("should wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(40);
                    });

                    it("should not crush the text", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                describe("naturally widthed child with short text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: shortText });
                    });

                    it("should not wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should naturally width the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                describe("naturally widthed child with long word", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: shortText + ' ' + longWord });
                    });

                    it("should wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(40);
                    });

                    it("should not allow the child's width to expand beyond the container", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                describe("naturally widthed child without text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { height: 20 });
                    });

                    it("should natuarally width the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                // TODO: reenable this when https://sencha.jira.com/browse/EXTJSIV-5884 is fixed.
                xdescribe("shrink wrapped child", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, {
                            xtype: 'panel',
                            shrinkWrap: 3,
                            html: '<div style="width:20px;height:20px;"></div>'
                        });
                    });

                    it("should not alter the width of the child", function() {
                        expect(panel.child().getWidth()).toBe(20);
                    });

                    it("should not alter the height of the child", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });
                });

                describe("overflow", function() {

                    describe("overflow x and y auto", function() {
                        var overflowParentConfig = Ext.apply({}, { autoScroll: true }, parentConfig);

                        describe("large vertical, no horizontal", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { anchor: '-2', height: 180 });
                            });

                            it("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(200);
                            });

                            it("should not have a horizontal scrollbar", function() {
                                expect(panel.body.dom.clientHeight).toBe(98);
                            });

                            it("should adjust anchor for scrollbar width", function() {
                                expect(panel.child().getWidth()).toBe(76 - scrollbarWidth);
                            });
                        });

                        describe("small vertical, no horizontal", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { anchor: '-2', height: 79 });
                            });

                            var todoIt = Ext.isIE9m && !shrinkWrap ? xit : it;

                            todoIt("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(99);
                            });

                            it("should not have a horizontal scrollbar", function() {
                                expect(panel.body.dom.clientHeight).toBe(98);
                            });

                            it("should adjust anchor for scrollbar width", function() {
                                expect(panel.child().getWidth()).toBe(76 - scrollbarWidth);
                            });
                        });

                        describe("large horizontal, no vertical", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { height: 20, width: 180 });
                            });

                            var todoIt = Ext.isIE9 ? xit : it;

                            it("should have the correct scroll width", function() {
                                expect(panel.body.dom.scrollWidth).toBe(200);
                            });

                            todoIt("should not have a vertical scrollbar", function() {
                                expect(panel.body.dom.clientWidth).toBe(98);
                            });
                        });

                        describe("small horizontal, no vertical", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { height: 20, width: 79 });
                            });

                            var todoIt = Ext.isIE9m ? xit : it;

                            it("should have the correct scroll width", function() {
                                expect(panel.body.dom.scrollWidth).toBe(99);
                            });

                            todoIt("should not have a vertical scrollbar", function() {
                                expect(panel.body.dom.clientWidth).toBe(98);
                            });
                        });

                        describe("large vertical, large horizontal", function() {
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

                        describe("large vertical, small horizontal", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { height: 180, width: 79 - scrollbarWidth });
                            });

                            it("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(200);
                            });

                            it("should have the correct scroll width", function() {
                                expect(panel.body.dom.scrollWidth).toBe(99 - scrollbarWidth);
                            });
                        });

                        describe("small vertical, large horizontal", function() {
                            beforeEach(function() {
                                makePanel(overflowParentConfig, { height: 79 - scrollbarWidth, width: 180 });
                            });

                            it("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(99 - scrollbarWidth);
                            });

                            it("should have the correct scroll width", function() {
                                expect(panel.body.dom.scrollWidth).toBe(200);
                            });
                        });
                    });

                    describe("overflow x auto, overflow y scroll", function() {
                        var overflowParentConfig = Ext.apply({}, { style: 'overflow-x:auto;overflow-y:scroll;' }, parentConfig);
                        // TODO

                    });

                    describe("overflow x scroll, overflow y auto", function() {
                        var overflowParentConfig = Ext.apply({}, { style: 'overflow-x:scroll;overflow-y:auto;' }, parentConfig);

                        // TODO
                    });

                    describe("overflow x and y scroll", function() {
                        var overflowParentConfig = Ext.apply({}, { style: 'overflow:scroll;' }, parentConfig);

                        // TODO
                    });
                });

                describe("percentage sized children", function() {
                    describe("overflow hidden", function() {
                        beforeEach(function() {
                            makePanel(parentConfig, { style: 'height: 50%; width: 50%;' });
                        });

                        it("should width the child correctly", function() {
                            expect(panel.child().getWidth()).toBe(43);
                        });

                        it("should height the child correctly", function() {
                            expect(panel.child().getHeight()).toBe(43);
                        });
                    });

                    describe("overflow auto", function() {
                        var overflowParentConfig = Ext.apply({}, { style: 'overflow:scroll;' }, parentConfig);

                        beforeEach(function() {
                            makePanel(overflowParentConfig, { style: 'height: 50%; width: 50%;' });
                        });

                        it("should width the child correctly", function() {
                            expect(panel.child().getWidth()).toBe(43);
                        });

                        it("should height the child correctly", function() {
                            expect(panel.child().getHeight()).toBe(43);
                        });
                    });
                });

                describe("autoScroll with no scrollbars", function() {
                    var overflowParentConfig = Ext.apply({}, { autoScroll: true }, parentConfig);

                    beforeEach(function() {
                        makePanel(overflowParentConfig, [
                            { anchor: '100% 100%' }
                        ]);
                    });

                    it("should not reserve space for a vertical scrollbar when sizing the child", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                    });

                    it("should not reserve space for a horizontal scrollbar when sizing the child", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(78);
                    });
                });
            });

            describe("configured height, shrink wrap width", function() {
                var parentConfig = {
                    height: 100,
                    shrinkWrap: 1
                };

                describe("anchoring items using percentages", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { anchor: '100%, 50%', html: '<div style="width:78px"></div>' },
                            { anchor: '50%, 50%' }
                        ]);
                    });

                    // TODO: waiting on https://sencha.jira.com/browse/EXTJSIV-5896
                    xit("should shrink wrap to the width of the widest child item", function() {
                        expect(panel.getWidth()).toBe(100);
                        expect(panel.items.getAt(0).getWidth()).toBe(80);
                        expect(panel.items.getAt(1).getWidth()).toBe(80);
                    });

                    // TODO: this spec will need updating when https://sencha.jira.com/browse/EXTJSIV-5892 is fixed
                    // heights should be 38 if the collapsing margins are accounted for.
                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(35);
                        expect(panel.items.getAt(1).getHeight()).toBe(35);
                    });
                });

                describe("anchoring items using offsets", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            // TODO: should we be able to use bottom anchors insead of using a fixed height?
                            // see https://sencha.jira.com/browse/EXTJSIV-5893
                            { anchor: '0', height: 37, html: '<div style="width:78px"></div>' },
                            { anchor: '-43', height: 37 }
                        ]);
                    });

                    // TODO: waiting on https://sencha.jira.com/browse/EXTJSIV-5896
                    xit("should shrink wrap to the width of the widest child item", function() {
                        expect(panel.getWidth()).toBe(100);
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                        expect(panel.items.getAt(1).getWidth()).toBe(78);
                    });

                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(37);
                        expect(panel.items.getAt(1).getHeight()).toBe(37);
                    });
                });

                describe("auto width child with text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: longText, height: 20 });
                    });

                    it("should not wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should shrink wrap the width", function() {
                        expect(panel.getWidth()).toBe(panel.child().getWidth() + 22);
                    });
                });

                describe("overflow", function() {
                    var overflowParentConfig = Ext.apply({}, { bodyStyle: 'overflow:auto;' }, parentConfig);

                    describe("vertical", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { anchor: '0', height: 180, html: '<div style="width:80px;"></div>' });
                        });

                        it("should have the correct scroll height", function() {
                            expect(panel.body.dom.scrollHeight).toBe(200);
                        });

                        // TODO: waiting on https://sencha.jira.com/browse/EXTJSIV-5896
                        xit("should shrink wrap the width", function() {
                            expect(panel.getWidth()).toBe(100);
                        });

                        // TODO: enable this spec when https://sencha.jira.com/browse/EXTJSIV-5895 is fixed.
                        xit("should not have horizontal overflow", function() {
                            expect(panel.body.dom.scrollWidth).toBe(panel.getWidth() - scrollbarWidth);
                        });
                    });

                });
            });

            describe("configured width, shrink wrap height", function() {
                var parentConfig = {
                    width: 100
                };

                describe("anchoring items using percentages", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { anchor: '100%', height: 37 },
                            { anchor: '50%', height: 37 }
                        ]);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });

                    it("should width the items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                        expect(panel.items.getAt(1).getWidth()).toBe(35);
                    });

                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(37);
                        expect(panel.items.getAt(1).getHeight()).toBe(37);
                    });
                });

                describe("anchoring items using offsets", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            // TODO: should we be able to use bottom anchors insead of using a fixed height?
                            // see https://sencha.jira.com/browse/EXTJSIV-5893
                            { anchor: '0', height: 37 },
                            { anchor: '-43', height: 37 }
                        ]);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });

                    it("should width the items correctly", function() {
                        expect(panel.items.getAt(0).getWidth()).toBe(78);
                        expect(panel.items.getAt(1).getWidth()).toBe(35);
                    });

                    it("should height the items correctly", function() {
                        expect(panel.items.getAt(0).getHeight()).toBe(37);
                        expect(panel.items.getAt(1).getHeight()).toBe(37);
                    });
                });

                describe("naturally widthed child with long text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: longText });
                    });

                    it("should wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(40);
                    });

                    it("should not crush the text", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(62);
                    });
                });

                describe("naturally widthed child with short text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: shortText });
                    });

                    it("should not wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should naturally width the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(42);
                    });
                });

                describe("naturally widthed child with long word", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: shortText + ' ' + longWord });
                    });

                    it("should wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(40);
                    });

                    it("should not allow the child's width to expand beyond the container", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(62);
                    });
                });

                describe("naturally widthed child without text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { height: 20 });
                    });

                    it("should naturally width the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(42);
                    });
                });

                // TODO: reenable this when https://sencha.jira.com/browse/EXTJSIV-5884 is fixed.
                xdescribe("shrink wrapped child", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, {
                            xtype: 'panel',
                            shrinkWrap: 3,
                            html: '<div style="width:20px;height:20px;"></div>'
                        });
                    });

                    it("should not alter the width of the child", function() {
                        expect(panel.child().getWidth()).toBe(20);
                    });

                    it("should not alter the height of the child", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(40);
                    });
                });

                describe("overflow", function() {
                    var overflowParentConfig = Ext.apply({}, { bodyStyle: 'overflow:auto;' }, parentConfig);

                    describe("horizontal", function() {
                        beforeEach(function() {
                            makePanel(overflowParentConfig, { height: 78 - scrollbarHeight, width: 180 });
                        });

                        it("should have the correct scroll width", function() {
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });

                        // TODO: https://sencha.jira.com/browse/EXTJSIV-5911
                        xit("should shrink wrap the height", function() {
                            expect(panel.getHeight()).toBe(100);
                        });

                        // TODO: enable this spec when https://sencha.jira.com/browse/EXTJSIV-5895 is fixed
                        xit("should not have vertical overflow", function() {
                            expect(panel.body.dom.scrollHeight).toBe(panel.getHeight() - scrollbarHeight);
                        });
                    });
                });
            });

            describe("shrink wrap width and height", function() {
                var parentConfig = {
                    shrinkWrap: 3
                };

                describe("anchoring items using percentages", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, [
                            { anchor: '100%, 50%', html: '<div style="width:40px;height:20px;"></div>' },
                            { anchor: '50%, 50%', html: '<div style="width:20px;height:20px;"></div>' }
                        ]);
                    });

                    it("should shrink wrap to the width of the widest item", function() {
                        expect(panel.getWidth()).toBe(62);
                        expect(panel.items.getAt(0).getWidth()).toBe(40);
                        expect(panel.items.getAt(1).getWidth()).toBe(40);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(66);
                        expect(panel.items.getAt(0).getHeight()).toBe(20);
                        expect(panel.items.getAt(1).getHeight()).toBe(20);
                    });
                });

                describe("auto width child with text", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { html: longText, height: 20 });
                    });

                    it("should not wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should shrink wrap the width", function() {
                        expect(panel.getWidth()).toBe(panel.child().getWidth() + 22);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(42);
                    });
                });

                describe("child with configured width", function() {
                    beforeEach(function() {
                        makePanel(parentConfig, { width: 78, height: 78 });
                    });

                    it("should shrink wrap the width", function() {
                        expect(panel.getWidth()).toBe(100);
                    });

                    it("should not alter the width of the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });

                    it("should shrink wrap the height", function() {
                        expect(panel.getHeight()).toBe(100);
                    });

                    it("should not alter the height of the child", function() {
                        expect(panel.child().getHeight()).toBe(78);
                    });
                });
            });

            xdescribe("stretching", function() {
                var failedLayouts,
                    absoluteDef = {
                        xtype: 'form',
                        layout: 'absolute',
                        defaultType: 'textfield',
                        items: [
                            {
                                x: 0,
                                y: 5,
                                xtype: 'label',
                                text: 'From:'
                            }, {
                                x: 55,
                                y: 0,
                                name: 'from',
                                hideLabel: true,
                                anchor: '100%'  // anchor width by %
                            }, {
                                x: 0,
                                y: 32,
                                xtype: 'label',
                                text: 'To:'
                            }, {
                                x: 55,
                                y: 27,
                                xtype: 'button',
                                text: 'Contacts...'
                            }, {
                                x: 127,
                                y: 27,
                                name: 'to',
                                hideLabel: true,
                                anchor: '100%'  // anchor width by %
                            }, {
                                x: 0,
                                y: 59,
                                xtype: 'label',
                                text: 'Subject:'
                            }, {
                                x: 55,
                                y: 54,
                                name: 'subject',
                                hideLabel: true,
                                anchor: '100%'  // anchor width by %
                            }
                        ]
                    },
                    horizontalTest = {
                        xtype: 'form',
                        layout: 'anchor',
                        defaultType: 'displayfield',
                        defaults: {
                            style: {
                                border: "solid red 1px"
                            }
                        },
                        items: [
                            // these three will all end up with odd widths
                            // all three need to stretch to the 150 minWidth
                            // of the first
                            {
                                value: 'a fairly long lable value',
                                anchor: '100%',
                                minWidth: 150
                            },
                            {
                                value: 'a label',
                                anchor: '100%'
                            },
                            {
                                value: 'a',
                                anchor: '100%'
                            }
                        ]
                    },
                    verticalTest = {
                        xtype: 'form',
                        layout: 'absolute',
                        defaultType: 'displayfield',
                        defaults: {
                            style: {
                                border: "solid red 1px"
                            }
                        },
                        items: [
                            {
                                x: 0,
                                y: 0,
                                value: 'a fairly long lable value',
                                minWidth: 150
                            },
                            {
                                x: 0,
                                y: 30,
                                value: 'a label'
                            },
                            {
                                x: 0,
                                y: 60,
                                value: 'a'
                            },
                            // bit odd here, but can be done through the API
                            // should probably have a better way to cope with
                            // two side-by-side components that need to anchor
                            // to the bottom (or disallow altogether, overlap detection maybe...)
                            // it's concievable that we may need to stretch the first one
                            // down to match the second one
                            {
                                x: 0,
                                y: 90,
                                value: ['a', 'b', 'c', 'd']
                                    .join('<br>'),
                                anchor: '-30 100%'
                            },
                            {
                                x: 30,
                                y: 90,
                                width: 30,
                                height: 200,
                                value: ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h']
                                    .join('<br>'),
                                anchor: '100% 100%'
                            }
                        ]
                    },
                    getChildren = function(comp) {
                        return comp.items.items;
                    },
                    comp,
                    getFailedLayoutCount = function() {
                        return (Ext.failedLayouts || 0) - failedLayouts;
                    };

                beforeEach(function() {
                    Ext.define('AnchorTest.StretchPanel', {
                        extend: 'Ext.container.Container',
                        xtype: 'stretchpanel',
                        shrinkWrap: 3,
                        layout: {
                            type: 'table',
                            columns: 1
                        },
                        initComponent: function() {
                            if (this.columns) {
                                this.layout = Ext.apply(this.layout, { columns: this.columns });
                            }

                            this.callParent();
                        }
                    });

                    failedLayouts = (Ext.failedLayouts || 0);
                });

                afterEach(function() {
                    Ext.undefine('AnchorTest.StretchPanel');

                    if (comp) {
                        comp.destroy();
                        comp = null;
                    }
                });

                describe("shrinkWrap", function() {

                    it("should not cause layout failures when shrinkWrapped", function() {
                        comp = Ext.ComponentManager.create({
                            renderTo: Ext.getBody(),
                            xtype: 'stretchpanel',
                            items: [
                                absoluteDef
                            ]
                        });

                        expect(getFailedLayoutCount()).toBe(0);
                    });

                    it("should shrinkWrap horizontally", function() {

                        comp = Ext.ComponentManager.create({
                            renderTo: Ext.getBody(),
                            xtype: 'stretchpanel',
                            items: [
                                horizontalTest
                            ]
                        });

                        expect(comp.getWidth()).toBe(150);
                    });

                    it("should stretchMax components horizontally when shrinkWrapped", function() {

                        comp = Ext.ComponentManager.create({
                            renderTo: Ext.getBody(),
                            xtype: 'stretchpanel',
                            items: [
                                horizontalTest
                            ]
                        });

                        var children = getChildren(comp);

                        expect(getFailedLayoutCount()).toBe(0);
                        expect(comp.getWidth()).toBe(150);
                        expect(getChildren(children[0])[0].getWidth()).toBe(150);
                        expect(getChildren(children[0])[1].getWidth()).toBe(150);
                        expect(getChildren(children[0])[2].getWidth()).toBe(150);

                    });

                    it("should shrinkWrap vertically", function() {

                        comp = Ext.ComponentManager.create({
                            renderTo: Ext.getBody(),
                            xtype: 'stretchpanel',
                            items: [
                                verticalTest
                            ]
                        });

                        expect(getFailedLayoutCount()).toBe(0);
                        expect(comp.getWidth()).toBe(150);
                        expect(comp.getHeight()).toBe(290);
                    });

                    it("should stretchMax compnents vertically when shrinkWrapped", function() {

                        comp = Ext.ComponentManager.create({
                            renderTo: Ext.getBody(),
                            xtype: 'stretchpanel',
                            items: [
                                verticalTest
                            ]
                        });

                        var children = getChildren(comp);

                        expect(getFailedLayoutCount()).toBe(0);
                        expect(comp.getWidth()).toBe(150);
                        expect(comp.getHeight()).toBe(290);
                        expect(getChildren(children[0])[0].getWidth()).toBe(150);
                        expect(getChildren(children[0])[1].getWidth()).toBe(150);
                        expect(getChildren(children[0])[2].getWidth()).toBe(150);
                        expect(getChildren(children[0])[3].getHeight()).toBe(200);
                        expect(getChildren(children[0])[4].getHeight()).toBe(200);
                    });
                });
            });

        });
    }

    createSuite();
    createSuite(true);

    describe("layout failures", function() {
        it("should work with a minHeight child", function() {
            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 200,
                height: 100,
                layout: "anchor",
                items: [{
                    minHeight: 70
                }]
            });

            expect(ct.items.first().getHeight()).toBe(70);
            ct.destroy();
        });
    });

    it("should shrinkwrap height correctly when it contains both liquidLayout and non-liquidLayout items", function() {
        var panel = Ext.widget({
            renderTo: document.body,
            xtype: 'form',
            id: 'main-form',
            width: 400,
            bodyPadding: 5,
            defaults: {
                anchor: '100%'
            },
            items: [{
                xtype: 'fieldcontainer',
                layout: 'hbox',
                items: [{
                    xtype: 'component',
                    flex: 1,
                    style: 'height: 50px; background-color: green;',
                    html: '&nbsp;'
                }]
            }, {
                // We must use a Component. Some old IEs insist on using
                // content-sizing box model resulting in the extra border height.
                xtype: 'component',
                height: 100,
                margin: '0',
                allowBlank: false
            }]
        });

        expect(panel.getHeight()).toBe(167);
        panel.destroy();
    });
});
