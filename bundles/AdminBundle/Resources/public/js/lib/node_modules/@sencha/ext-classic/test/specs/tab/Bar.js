topSuite("Ext.tab.Bar",
    ['Ext.tab.Panel', 'Ext.layout.container.boxOverflow.Menu'],
function() {
    var tabBar;

    function createTabBar(config) {
        tabBar = new Ext.tab.Bar(Ext.apply({}, config));
    }

    function makeBar(config) {
        config = Ext.apply({
            renderTo: document.body
        }, config);

        tabBar = new Ext.tab.Bar(config);
    }

    function doClick(targetId, tab) {
        tabBar.onClick({
            // mock event object can go here if needed
            getTarget: function() {
                return (tab) ? tab.el : null;
            }
        }, {
            // target el
            id: targetId
        });
    }

    function makeTabs(n, prop) {
        var tabs = [],
            i, o;

        prop = prop || 'text';

        for (i = 1; i <= n; ++i) {
            o = {};
            o[prop] = 'ItemText' + i;
            tabs.push(o);
        }

        return tabs;
    }

    function expectVisible(item) {
        var scroller = tabBar.getScrollable(),
            curPosition = scroller.getPosition();

        expect(item.el.getScrollIntoViewXY(scroller.getElement(), curPosition.x, curPosition.y).x - curPosition.x).toBe(0);
    }

    function expectNotVisible(item) {
        var scroller = tabBar.getScrollable(),
            curPosition = scroller.getPosition();

        expect(item.el.getScrollIntoViewXY(scroller.getElement(), curPosition.x, curPosition.y).x - curPosition.x).not.toBe(0);
    }

    afterEach(function() {
        Ext.destroy(tabBar);
        tabBar = null;
    });

    describe("layout", function() {
        it("should be hbox by default", function() {
            createTabBar();
            var layout = tabBar.getLayout();

            expect(layout.type).toBe('hbox');
        });

        it("should have pack start by default", function() {
            createTabBar();
            var layout = tabBar.getLayout();

            expect(layout.pack).toBe('start');
        });

        it("should allow custom configuration", function() {
            createTabBar({
                layout: {
                    pack: 'center'
                }
            });
            var layout = tabBar.getLayout();

            expect(layout.pack).toBe('center');
        });

        it("should have a default height when there are no tabs", function() {
            var tabPanel = Ext.create({
                xtype: 'tabpanel',
                renderTo: document.body,
                width: 100,
                height: 100
            });

            expect(tabPanel.getTabBar().getHeight()).toBe(27);

            tabPanel.destroy();
        });
    });

    describe("closing a tab", function() {
        var closeListener,
            destroyListener,
            tabPanel,
            item1,
            item1CloseButton,
            item2;

        beforeEach(function() {
            closeListener = jasmine.createSpy();
            destroyListener = jasmine.createSpy();
            tabPanel = Ext.create('Ext.tab.Panel', {
                renderTo: Ext.getBody(),
                width: 200,
                height: 100,
                items: [{
                    id: 'item1',
                    title: 'Tab 1',
                    closable: true,
                    listeners: {
                        close: closeListener,
                        destroy: destroyListener
                    }
                }, {
                    id: 'item2',
                    title: 'Tab 2',
                    closable: true
                }]
            });
            spyOn(tabPanel, 'remove').andCallThrough();
            spyOn(tabPanel.getTabBar(), 'remove').andCallThrough();
            item1 = Ext.getCmp('item1');
            item1CloseButton = item1.tab.closeEl.dom;
            item2 = Ext.getCmp('item2');
        });

        afterEach(function() {
            tabPanel.destroy();
        });

        it("should fire 'close' event in the item", function() {
            jasmine.fireMouseEvent(item1CloseButton, 'click');
            expect(closeListener).toHaveBeenCalled();
        });

        it("should fire 'destroy' event in the item", function() {
            jasmine.fireMouseEvent(item1CloseButton, 'click');
            expect(destroyListener).toHaveBeenCalled();
        });

        it("should remove card from tabPanel", function() {
            jasmine.fireMouseEvent(item1CloseButton, 'click');
            expect(tabPanel.remove).toHaveBeenCalledWith(item1);
        });

        it("should remove tab from tabBar", function() {
            // backup tab, since item will no longer have a tab after being removed
            var tab = item1.tab;

            jasmine.fireMouseEvent(item1CloseButton, 'click');
            expect(tabPanel.getTabBar().remove).toHaveBeenCalledWith(tab);
        });

        it("should activate next tab", function() {
            jasmine.fireMouseEvent(item1CloseButton, 'click');
            expect(tabPanel.activeTab).toBe(item2);
        });
    });

    xdescribe("clicking on a tab", function() {
        var tab, cardLayout;

        describe("if the tab is enabled", function() {
            beforeEach(function() {
                cardLayout = {
                    setActiveItem: jasmine.createSpy()
                };

                createTabBar({
                    cardLayout: cardLayout
                });

                tabBar.add({
                    xtype: 'tab',
                    id: 'tab1',
                    card: {
                        some: 'card'
                    },
                    tabBar: tabBar
                });

                tabBar.render(document.body);

                tab = tabBar.getComponent('tab1');

                spyOn(tabBar, 'setActiveTab').andCallThrough();
            });

            afterEach(function() {
                tabBar.destroy();
            });

            it("should call setActiveTab", function() {
                doClick('tab1', tab);
                expect(tabBar.setActiveTab).toHaveBeenCalledWith(tab);
            });

            it("should fire the 'change' event", function() {
                var callFn;

                tabBar.on('change', callFn = jasmine.createSpy(), this);

                doClick('tab1', tab);
                expect(callFn).toHaveBeenCalled();
            });

            xit("should set the cardLayout's card to the tab's card", function() {
                doClick('tab1');
                /*
                 * Currently the layout is not called if the component is not rendered
                 * because it causes a null error inside CardLayout. This is either a
                 * change in behavior or a bug in the layout, but either way it invalidates
                 * this test for the time being...
                 */
                expect(cardLayout.setActiveItem).toHaveBeenCalledWith(tab.card);
            });

            describe("the 'change' event", function() {
                var args;

                beforeEach(function() {
                    tabBar.on('change', function() {
                        args = arguments;
                    }, this);

                    doClick('tab1');
                });

                it("should have a reference to the tabBar", function() {
                    expect(args[0]).toEqual(tabBar);
                });

                it("should have a reference to the tab", function() {
                    expect(args[1]).toEqual(tab);
                });

                it("should have a reference to the tab's card", function() {
                    expect(args[2]).toEqual(tab.card);
                });
            });
        });

        describe("if the tab disabled config is true", function() {
            var cardLayout, tab1, tab2;

            beforeEach(function() {
                cardLayout = {
                    setActiveItem: jasmine.createSpy()
                };

                createTabBar({
                    cardLayout: cardLayout
                });

                tabBar.add({
                    xtype: 'tab',
                    id: 'tab1',
                    card: {
                        some: 'card'
                    },
                    tabBar: tabBar
                }, {
                    xtype: 'tab',
                    id: 'tab2',
                    disabled: true,
                    card: {
                        other: 'card'
                    },
                    tabBar: tabBar
                });

                tab1 = tabBar.items.items[0];
                tab2 = tabBar.items.items[1];
            });

            afterEach(function() {
                tabBar.destroy();
            });

            it("should set the tab instance to disabled", function() {
                expect(tabBar.getComponent('tab2').disabled).toBe(true);
            });

            it("should not call setActiveItem on the layout", function() {
                doClick('tab2');
                expect(cardLayout.setActiveItem).not.toHaveBeenCalled();
            });
        });
    });

    describe("ensureTabVisible", function() {
        var items;

        function makeScrollTabs(cfg) {
            createTabBar(Ext.apply({
                renderTo: Ext.getBody(),
                width: 300,
                items: makeTabs(10)
            }, cfg));
            items = tabBar.items;
        }

        afterEach(function() {
            items = null;
        });

        describe("arguments", function() {
            it("should default to the activeTab", function() {
                makeScrollTabs();
                var item = items.last();

                tabBar.setActiveTab(item);
                // Go to the front
                tabBar.layout.overflowHandler.scrollBy(-1000, false);
                expectNotVisible(item);
                tabBar.ensureTabVisible();
                expectVisible(item);
            });

            it("should accept a tab", function() {
                makeScrollTabs();
                var item = items.getAt(8);

                expectNotVisible(item);
                tabBar.ensureTabVisible(8);
                expectVisible(item);
            });

            describe("index", function() {
                it("should accept 0", function() {
                    makeScrollTabs();
                    var item = items.first();

                    tabBar.layout.overflowHandler.scrollBy(5000, false);
                    expectNotVisible(item);
                    tabBar.ensureTabVisible(0);
                    expectVisible(item);
                });

                it("should accept a non-zero index", function() {
                    makeScrollTabs();
                    var item = items.getAt(3);

                    tabBar.layout.overflowHandler.scrollBy(5000, false);
                    expectNotVisible(item);
                    tabBar.ensureTabVisible(3);
                    expectVisible(item);
                });
            });

            describe("tab panel items", function() {
                var tabPanel;

                beforeEach(function() {
                    tabPanel = new Ext.tab.Panel({
                        renderTo: Ext.getBody(),
                        width: 300,
                        items: makeTabs(10, 'title')
                    });
                    tabBar = tabPanel.getTabBar();
                });

                afterEach(function() {
                    tabPanel.destroy();
                    tabPanel = null;
                });

                it("should accept a tabpanel item", function() {
                    var item = tabBar.items.last();

                    expectNotVisible(item);
                    tabBar.ensureTabVisible(tabPanel.items.last());
                    expectVisible(item);
                });

                it("should ignore components not in the tab panel", function() {
                    var c = new Ext.Component();

                    var item = tabBar.items.first();

                    expectVisible(item);
                    tabBar.ensureTabVisible(c);
                    expectVisible(item);

                    c.destroy();
                });
            });
        });

        it("should not cause issue if there is no scroller", function() {
            makeScrollTabs({
                width: 3000
            });
            expect(function() {
                tabBar.ensureTabVisible(items.last());
            }).not.toThrow();
        });
    });

    describe("overflow menu & active tab", function() {
        beforeEach(function() {
            createTabBar({
                renderTo: document.body,
                width: 150,
                layout: {
                    overflowHandler: 'menu'
                },
                items: makeTabs(3)
            });
        });

        it("should activate the tab when selected from the overflow menu", function() {
            var menu = tabBar.layout.overflowHandler.menu,
                item, tab;

            menu.show();
            item = menu.items.first();
            tab = item.masterComponent;
            jasmine.fireMouseEvent(item, 'click');

            expect(tabBar.activeTab).toEqual(tab);
        });

        it("should focus the menuTrigger when card is selected from the overflow menu", function() {
            var handler = tabBar.layout.overflowHandler,
                menu = handler.menu,
                trigger = handler.menuTrigger,
                item;

            menu.show();
            item = menu.items.first();
            jasmine.fireMouseEvent(item, 'click');

            jasmine.expectFocused(trigger);
        });
    });

    describe("scroll & active tab", function() {
        var items;

        function makeScrollTabs(cfg) {
            createTabBar(Ext.apply({
                renderTo: Ext.getBody(),
                width: 300,
                items: makeTabs(10)
            }, cfg));
            items = tabBar.items;
        }

        afterEach(function() {
            items = null;
        });

        it("should have the the active tab scrolled to", function() {
            makeScrollTabs();
            var item = items.last();

            tabBar.setActiveTab(item);
            expectVisible(item);

            item = items.first();
            tabBar.setActiveTab(item);
            expectVisible(item);
        });

        describe("active item change", function() {
            describe("with ensureActiveVisibleOnChange: false", function() {
                beforeEach(function() {
                    makeScrollTabs({
                        ensureActiveVisibleOnChange: false
                    });
                });

                it("should not move the item into full view when changing the text", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setText('Longer text that will cause a scroll');
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectNotVisible(item);
                });

                it("should move the item into full view when changing the icon", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setIcon(Ext.BLANK_IMAGE_URL);
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectNotVisible(item);
                });

                it("should move the item into full view when changing the iconCls", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setIconCls('someIconCls');
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectNotVisible(item);
                });

                it("should move the item into full view when changing the glyph", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setGlyph(100);
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectNotVisible(item);
                });
            });

            describe("with ensureActiveVisibleOnChange: true", function() {
                beforeEach(function() {
                    makeScrollTabs({
                        ensureActiveVisibleOnChange: true
                    });
                });

                it("should move the item into full view when changing the text", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setText('Longer text that will cause a scroll');
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectVisible(item);
                });

                it("should move the item into full view when changing the icon", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setIcon(Ext.BLANK_IMAGE_URL);
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectVisible(item);
                });

                it("should move the item into full view when changing the iconCls", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setIconCls('someIconCls');
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectVisible(item);
                });

                it("should move the item into full view when changing the glyph", function() {
                    var item = items.last(),
                        width = item.getWidth();

                    tabBar.setActiveTab(item);
                    tabBar.ensureTabVisible(0);
                    item.setGlyph(100);
                    expect(item.getWidth()).toBeGreaterThan(width);
                    expectVisible(item);
                });
            });
        });
    });

    xdescribe("setting the active tab", function() {
        var tab;

        beforeEach(function() {
            createTabBar();

            tabBar.add({
                xtype: 'tab',
                card: {
                    some: 'card'
                },
                tabBar: tabBar
            });

            tab = tabBar.getComponent(0);
        });

        it("should set the activeTab property to that tab", function() {
            tabBar.setActiveTab(tab);

            expect(tabBar.activeTab).toEqual(tab);
        });
    });

    describe('moving tab items', function() {
        var tabPanel;

        beforeEach(function() {
            tabPanel = new Ext.tab.Panel({
                deferredRender: false,
                renderTo: Ext.getBody(),
                width: 300,
                items: [{
                    title: 'Tab 1',
                    html: 'Tab 1',
                    id: 'tab1'
                }, {
                    title: 'Tab 2',
                    html: 'Tab 2',
                    id: 'tab2'
                }, {
                    title: 'Tab 3',
                    html: 'Tab 3',
                    id: 'tab3'
                }]
            });
            tabBar = tabPanel.getTabBar();
        });

        afterEach(function() {
            tabPanel.destroy();
            tabPanel = null;
        });

        it('should move the underlying cards to keep the orders synchronized', function() {
            // Check initial state
            expect(tabBar.getComponent(0).text).toBe('Tab 1');
            expect(tabBar.getComponent(1).text).toBe('Tab 2');
            expect(tabBar.getComponent(2).text).toBe('Tab 3');

            // Move the first tab item to the end
            tabBar.move(0, 2);
            // Check the reordered state
            expect(tabBar.getComponent(0).text).toBe('Tab 2');
            expect(tabBar.getComponent(1).text).toBe('Tab 3');
            expect(tabBar.getComponent(2).text).toBe('Tab 1');

            tabPanel.move(0, 2);
            // Check the movemenrt of the cards has not blindly passed
            // the movement on and de-synchronized the tab items order.
            expect(tabBar.getComponent(0).text).toBe('Tab 2');
            expect(tabBar.getComponent(1).text).toBe('Tab 3');
            expect(tabBar.getComponent(2).text).toBe('Tab 1');
        });
    });

    describe("FocusableContainer", function() {
        describe("no tabs", function() {
            beforeEach(function() {
                makeBar();
            });

            it("should be inactive", function() {
                expect(tabBar.isFocusableContainerActive()).toBeFalsy();
            });
        });

        describe("with tabs", function() {
            beforeEach(function() {
                makeBar({
                    items: [{
                        xtype: 'tab',
                        text: 'foo'
                    }]
                });
            });

            it("should be active", function() {
                expect(tabBar.isFocusableContainerActive()).toBeTruthy();
            });

            it("should have tabbable tab", function() {
                expect(tabBar.down('tab')).toHaveAttr('tabIndex', 0);
            });
        });

        describe("adding tabs", function() {
            beforeEach(function() {
                makeBar();

                tabBar.add({
                    items: [{
                        xtype: 'tab',
                        text: 'bar'
                    }]
                });
            });

            it("should be active", function() {
                expect(tabBar.isFocusableContainerActive()).toBeTruthy();
            });

            it("should have tabbable tab", function() {
                expect(tabBar.down('tab')).toHaveAttr('tabIndex', 0);
            });
        });

        describe("removing tabs", function() {
            beforeEach(function() {
                makeBar({
                    items: [{
                        xtype: 'tab',
                        text: 'foo'
                    }]
                });

                tabBar.remove(0);
            });

            it("should be deactivated", function() {
                expect(tabBar.isFocusableContainerActive()).toBeFalsy();
            });
        });
    });
});
