topSuite("Ext.resizer.Splitter",
    ['Ext.Panel', 'Ext.layout.container.Border'],
function() {
    var splitter, c;

    function makeContainer(splitterCfg) {
        splitter = new Ext.resizer.Splitter(splitterCfg || {});

        c = new Ext.Container({
            layout: 'hbox',
            width: 500,
            height: 500,
            defaultType: 'container',
            items: [{
                html: 'foo',
                flex: 1
            }, splitter, {
                html: 'bar',
                flex: 1
            }],
            renderTo: Ext.getBody()
        });
    }

    afterEach(function() {
        if (c) {
            c.destroy();
        }

        splitter = c = null;
    });

    describe("init", function() {
        describe("the tracker", function() {
            it("should create a SplitterTracker by default", function() {
                makeContainer();

                expect(splitter.tracker instanceof Ext.resizer.SplitterTracker).toBe(true);
            });

            it("should honor a custom tracker config", function() {
                makeContainer({
                    tracker: {
                        xclass: 'Ext.resizer.BorderSplitter',
                        foo: 'baz'
                    }
                });

                expect(splitter.tracker instanceof Ext.resizer.BorderSplitter).toBe(true);
                expect(splitter.tracker.foo).toBe('baz');
            });
        });

        describe("collapsing", function() {
            function makeContainer(splitterCfg) {
                c = new Ext.container.Container({
                    renderTo: document.body,
                    layout: 'hbox',
                    width: 500,
                    height: 500,
                    items: [{
                        xtype: 'container',
                        itemId: 'foo',
                        html: 'foo',
                        flex: 1
                    }, Ext.apply({
                        xtype: 'splitter'
                    }, splitterCfg), {
                        xtype: 'panel',
                        itemId: 'bar',
                        collapsible: true,
                        html: 'bar'
                    }]
                });

                splitter = c.down('splitter');
            }

            describe("listeners", function() {
                it("should not attach collapse listeners when target is not a panel", function() {
                    makeContainer({ collapseTarget: 'prev' });

                    var item = c.down('#foo');

                    expect(item.hasListeners.collapse).not.toBeDefined();
                });

                it("should attach listeners when target is a panel", function() {
                    makeContainer();

                    var item = c.down('#bar');

                    expect(item.hasListeners.collapse).toBe(1);
                });
            });
        });
    });

    describe("splitter with border layout and iframes", function() {
        var iframe;

        beforeEach(function() {
            iframe = new Ext.Component({
                autoEl: {
                    tag: 'iframe',
                    src: 'about:blank'
                }
            });
            c = new Ext.panel.Panel({
                width: 400,
                height: 400,
                layout: 'border',
                renderTo: document.body,
                items: [{
                    xtype: 'panel',
                    width: 200,
                    region: 'west',
                    split: true,
                    collapsible: true,
                    animCollapse: false
                }, iframe]
            });
            splitter = c.down('splitter');
        });

        afterEach(function() {
            iframe.destroy();
            iframe = null;
        });

        it("should mask the iframes while resizing and unmask it when done", function() {
            var parentNode = Ext.fly(iframe.el.dom.parentNode);

            jasmine.fireMouseEvent(splitter, 'mousedown');
            expect(parentNode.isMasked()).toBe(true);

            jasmine.fireMouseEvent(splitter, 'mouseup');
            expect(parentNode.isMasked()).toBe(false);
        });

        it("should not mask iframes when clicking on the splitter collapseEl", function() {
            jasmine.fireMouseEvent(splitter.el.query('[data-ref=collapseEl]')[0], 'click');
            expect(c.down('panel').collapsed).toBe('left');
            expect(Ext.fly(iframe.el.dom.parentNode).isMasked()).toBe(false);
        });

        it("should not mask iframes when clicking the splitter and the panel is collapsed", function() {
            c.down('panel').collapse();
            jasmine.fireMouseEvent(splitter, 'mousedown');
            expect(Ext.fly(iframe.el.dom.parentNode).isMasked()).toBe(false);
            jasmine.fireMouseEvent(splitter, 'mouseup');
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            makeContainer();
        });

        it("should be tabbable", function() {
            expect(splitter.el.isTabbable()).toBe(true);
        });

        it("should have separator role", function() {
            expect(splitter).toHaveAttr('role', 'separator');
        });

        it("should have aria-orientation", function() {
            expect(splitter).toHaveAttr('aria-orientation', 'vertical');
        });
    });
});
