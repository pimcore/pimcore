topSuite("Ext.layout.container.Auto", ['Ext.Panel'], function() {
    function createSuite(shrinkWrap) {
        var suiteName = 'Ext.layout.container.Auto';

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

            function makeContainer(parentConfig, childConfig) {
                var child = Ext.apply({
                        xtype: 'component',
                        style: 'margin: 4px; line-height: 20px;'
                    }, childConfig),
                    parent = Ext.apply({
                        renderTo: document.body,
                        xtype: 'panel',
                        shrinkWrap: shrinkWrap || 2,
                        bodyPadding: '6',
                        items: [child]
                    }, parentConfig);

                panel = Ext.widget(parent);
            }

            afterEach(function() {
                panel.destroy();
            });

            describe("configured width and height", function() {
                var parentConfig = {
                    height: 100,
                    width: 100
                };

                describe("naturally widthed child with long text", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, { html: longText });
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
                        makeContainer(parentConfig, { html: shortText });
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
                        makeContainer(parentConfig, { html: shortText + ' ' + longWord });
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
                        makeContainer(parentConfig, { height: 20 });
                    });

                    it("should natuarally width the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                // TODO: reenable this when https://sencha.jira.com/browse/EXTJSIV-5884 is fixed.
                xdescribe("shrink wrapped child", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, {
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
                                makeContainer(overflowParentConfig, { height: 180, width: 20 });
                            });

                            it("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(200);
                            });

                            it("should not have a horizontal scrollbar", function() {
                                expect(panel.body.dom.clientHeight).toBe(98);
                            });
                        });

                        describe("small vertical, no horizontal", function() {
                            beforeEach(function() {
                                makeContainer(overflowParentConfig, { height: 79, width: 20 });
                            });

                            var todoIt = Ext.isIE9m && !shrinkWrap ? xit : it;

                            todoIt("should have the correct scroll height", function() {
                                expect(panel.body.dom.scrollHeight).toBe(99);
                            });

                            it("should not have a horizontal scrollbar", function() {
                                expect(panel.body.dom.clientHeight).toBe(98);
                            });
                        });

                        describe("large horizontal, no vertical", function() {
                            beforeEach(function() {
                                makeContainer(overflowParentConfig, { height: 20, width: 180 });
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
                                makeContainer(overflowParentConfig, { height: 20, width: 79 });
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
                                makeContainer(overflowParentConfig, { height: 180, width: 180 });
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
                                makeContainer(overflowParentConfig, { height: 180, width: 79 - scrollbarWidth });
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
                                makeContainer(overflowParentConfig, { height: 79 - scrollbarWidth, width: 180 });
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
                            makeContainer(parentConfig, { style: 'height: 50%; width: 50%;' });
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
                            makeContainer(overflowParentConfig, { style: 'height: 50%; width: 50%;' });
                        });

                        it("should width the child correctly", function() {
                            expect(panel.child().getWidth()).toBe(43);
                        });

                        it("should height the child correctly", function() {
                            expect(panel.child().getHeight()).toBe(43);
                        });
                    });
                });
            });

            describe("configured height, shrink wrap width", function() {
                var parentConfig = {
                    height: 100,
                    shrinkWrap: 1
                };

                describe("auto width child with text", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, { html: longText, height: 20 });
                    });

                    it("should not wrap the text", function() {
                        expect(panel.child().getHeight()).toBe(20);
                    });

                    it("should shrink wrap the width", function() {
                        expect(panel.getWidth()).toBe(panel.child().getWidth() + 22);
                    });
                });

                describe("child with configured width", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, { width: 78, height: 20 });
                    });

                    it("should shrink wrap the width", function() {
                        expect(panel.getWidth()).toBe(100);
                    });

                    it("should not alter the width of the child", function() {
                        expect(panel.child().getWidth()).toBe(78);
                    });
                });

                describe("overflow", function() {
                    var overflowParentConfig = Ext.apply({}, { bodyStyle: 'overflow:auto;' }, parentConfig);

                    describe("vertical", function() {
                        beforeEach(function() {
                            makeContainer(overflowParentConfig, { height: 180, width: 18 });
                        });

                        it("should have the correct scroll height", function() {
                            // Auto layout does not have managed overflow so we have to account for cross browser padding differences
                            expect(panel.body.dom.scrollHeight).toBe(200);
                        });

                        // TODO: https://sencha.jira.com/browse/EXTJSIV-5911
                        xit("should shrink wrap the width", function() {
                            expect(panel.getWidth()).toBe(40 + scrollbarWidth);
                        });
                    });

                });
            });

            describe("configured width, shrink wrap height", function() {
                var parentConfig = {
                    width: 100
                };

                describe("naturally widthed child with long text", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, { html: longText });
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
                        makeContainer(parentConfig, { html: shortText });
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
                        makeContainer(parentConfig, { html: shortText + ' ' + longWord });
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
                        makeContainer(parentConfig, { height: 20 });
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
                        makeContainer(parentConfig, {
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
                            makeContainer(overflowParentConfig, { height: 20, width: 180 });
                        });

                        it("should have the correct scroll width", function() {
                            // Auto layout does not have managed overflow so we have to account for cross browser padding differences
                            expect(panel.body.dom.scrollWidth).toBe(200);
                        });

                        // TODO: https://sencha.jira.com/browse/EXTJSIV-5911
                        xit("should shrink wrap the height", function() {
                            expect(panel.getHeight()).toBe(42 + scrollbarHeight);
                        });
                    });

                });
            });

            describe("shrink wrap width and height", function() {
                var parentConfig = {
                    shrinkWrap: 3
                };

                describe("auto width child with text", function() {
                    beforeEach(function() {
                        makeContainer(parentConfig, { html: longText, height: 20 });
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
                        makeContainer(parentConfig, { width: 78, height: 78 });
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

            describe("margin collapse", function() {
                var container;

                function makeContainer(childStyle) {
                    container = Ext.create('Ext.Container', {
                        renderTo: document.body,
                        items: [{
                            xtype: 'component',
                            margin: 5,
                            height: 90,
                            style: childStyle
                        }]
                    });
                }

                afterEach(function() {
                    container.destroy();
                });

                it("should contain the margins of of its child items", function() {
                    makeContainer();
                    expect(container.getHeight()).toBe(100);
                });

                it("should contain the margins of of its floated child items", function() {
                    makeContainer('float:left');
                    expect(container.getHeight()).toBe(100);
                });
            });

        });
    }

    createSuite();
    createSuite(true);
});
