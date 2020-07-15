topSuite("Ext.tab.Panel", ['Ext.form.field.Text', 'Ext.app.ViewModel'], function() {
    var tabPanel, fakeTabBar;

    function createTabPanel(config) {
        tabPanel = Ext.create(Ext.apply({
                xtype: 'tabpanel',
                renderTo: Ext.getBody()
            }, config));

        return tabPanel;
    }

    // creates a tab panel with x children
    function createTabPanelWithTabs(count, config) {
        var i,
            items = [];

        for (i = 0; i < count; i++) {
            items[i] = {
                xtype: 'panel',
                html: 'test ' + (i + 1),
                title: 'test ' + (i + 1),
                itemId: 'item' + (i + 1)
            };
        }

        return createTabPanel(Ext.apply({}, config, {
            items: items
        }));
    }

    afterEach(function() {
        if (tabPanel) {
            tabPanel = Ext.destroy(tabPanel);
        }
    });

    describe("active tab config on init", function() {
        it("should ignore the activeTab as a string ID if it doesn't exist", function() {
            createTabPanelWithTabs(3, {
                activeTab: 'foo'
            });
            expect(tabPanel.getActiveTab()).toBeUndefined();
        });
        it("should ignore the activeTab as a numeric index if it doesn't exist", function() {
            createTabPanelWithTabs(3, {
                activeTab: 4
            });
            expect(tabPanel.getActiveTab()).toBeUndefined();
        });
        it("should activate the activeTab by string ID if it does exist", function() {
            var foo = Ext.id(null, 'foo-'),
                bar = Ext.id(null, 'bar-');

            createTabPanelWithTabs(3, {
                items: [{
                    id: bar
                }, {
                    id: foo
                }],
                activeTab: foo
            });
            expect(tabPanel.getActiveTab().id).toEqual(foo);
        });
        it("should activate the activeTab by index if it does exist", function() {
            var foo = Ext.id(null, 'foo-'),
                bar = Ext.id(null, 'bar-');

            createTabPanelWithTabs(3, {
                items: [{
                    id: bar
                }, {
                    id: foo
                }],
                activeTab: 1
            });
            expect(tabPanel.getActiveTab().id).toEqual(foo);
        });
        it("should not set an active tab if null", function() {
            createTabPanelWithTabs(3, {
                activeTab: null
            });
            expect(tabPanel.getActiveTab()).toBeUndefined();
        });
        it("should set the first child tab as the active tab if none is configured", function() {
            createTabPanelWithTabs(3, {
            });
            expect(tabPanel.getActiveTab()).toBe(tabPanel.items.getAt(0));
        });
    });

    describe("activating other tabs on tab close", function() {
        var tb;

        it("should activate the next tab", function() {
            createTabPanelWithTabs(6, {
                renderTo: document.body,
                activeTab: 4
            });
            tb = tabPanel.tabBar;
            tb.closeTab(tb.items.items[4]);
            expect(tabPanel.items.indexOf(tabPanel.getActiveTab())).toEqual(4);
        });
        it("should activate the previously active tab", function() {
            createTabPanelWithTabs(6, {
                renderTo: document.body,
                activeTab: 5
            });
            tb = tabPanel.tabBar;
            tabPanel.setActiveTab(0);
            tabPanel.setActiveTab(4);
            tabPanel.tabBar.closeTab(tb.items.items[4]);
            expect(tabPanel.items.indexOf(tabPanel.getActiveTab())).toEqual(0);
        });
        it("should activate the new first tab when closing the first", function() {
            createTabPanelWithTabs(6, {
                renderTo: document.body,
                activeTab: 0
            });
            tb = tabPanel.tabBar;
            tabPanel.tabBar.closeTab(tb.items.items[0]);
            expect(tabPanel.items.indexOf(tabPanel.getActiveTab())).toEqual(0);
        });
    });

    describe("the tabBar", function() {
        beforeEach(function() {
            fakeTabBar = {
                something: 'yea'
            };
        });

        it("should be referenced as .tabBar", function() {
            createTabPanel();
            expect(tabPanel.tabBar).toBeDefined();
        });

        it("should be docked to the top", function() {
            createTabPanel();
            expect(tabPanel.tabBar.dock).toEqual('top');
        });

        it("should be accessible through getTabBar()", function() {
            createTabPanel();
            expect(tabPanel.getTabBar()).toBeDefined();
        });

        it("should accept additional config", function() {
            createTabPanel({
                tabBar: {
                    someConfig: 'something'
                }
            });

            expect(tabPanel.tabBar.someConfig).toEqual('something');
        });

        xdescribe("if there were no other dockedItems", function() {
            beforeEach(function() {
                createTabPanel();
            });

            it("should create the dockedItems MixedCollection", function() {
                expect(tabPanel.dockedItems instanceof Ext.util.MixedCollection).toBe(true);
            });

            it("should place the tabBar in the array", function() {
                expect(tabPanel.dockedItems.items[0]).toEqual(tabPanel.tabBar);
            });
        });

        describe("if there was an array of dockedItems", function() {
            beforeEach(function() {
                createTabPanel({
                    dockedItems: [
                        {
                            xtype: 'panel',
                            html: 'test',
                            dock: 'top'
                        }
                    ]
                });
            });

            it("should add the tabBar to the dockedItems", function() {
                expect(tabPanel.dockedItems.length).toEqual(2);
            });

            it("should place the tabBar as the last item in the array", function() {
                expect(tabPanel.dockedItems.items[1]).toEqual(tabPanel.tabBar);
            });
        });

        describe("if there was a single dockedItem, not in an array", function() {
            beforeEach(function() {
                createTabPanel({
                    dockedItems: {
                        xtype: 'panel',
                        html: 'test',
                        dock: 'top'
                    }
                });
            });

            xit("should turn the dockedItems into an array", function() {
                expect(tabPanel.dockedItems instanceof Ext.util.MixedCollection).toBe(true);
            });

            it("should add the tabBar to the dockedItems", function() {
                expect(tabPanel.dockedItems.length).toEqual(2);
            });

            it("should place the tabBar as the last item in the array", function() {
                expect(tabPanel.dockedItems.items[1]).toEqual(tabPanel.tabBar);
            });
        });

        describe("non tab items", function() {
            it("should not cause an error", function() {
                expect(function() {
                    createTabPanel({
                        tabBar: {
                            items: [{
                                xtype: 'button',
                                text: 'Foo'
                            }]
                        },
                        items: [{
                            title: 'Bar'
                        }, {
                            title: 'Baz'
                        }]
                    });
                }).not.toThrow();
                var items = tabPanel.tabBar.items;

                expect(items.getCount()).toBe(3);
                expect(items.last().getText()).toBe('Foo');
            });

            it("should not cause an error when using tabPosition", function() {
                expect(function() {
                    createTabPanel({
                        tabPosition: 'bottom',
                        tabBar: {
                            items: [{
                                xtype: 'button',
                                text: 'Foo'
                            }]
                        },
                        items: [{
                            title: 'Bar'
                        }, {
                            title: 'Baz'
                        }]
                    });
                }).not.toThrow();
                var items = tabPanel.tabBar.items;

                expect(items.getCount()).toBe(3);
                expect(items.last().getText()).toBe('Foo');
            });

            it("should allow a click on a button", function() {
                var spy = jasmine.createSpy('click'),
                    btn;

                runs(function() {
                    createTabPanel({
                        tabBar: {
                            items: [{
                                xtype: 'button',
                                itemId: 'theButton',
                                text: 'Foo'
                            }]
                        },
                        items: [{
                            title: 'Bar'
                        }, {
                            title: 'Baz'
                        }]
                    });
                    btn = tabPanel.down('#theButton');

                    btn.on('click', spy);
                });

                runs(function() {
                    expect(function() {
                        jasmine.fireMouseEvent(btn.getEl(), 'click');
                    }).not.toThrow();
                });

                waitsFor(function() { return !!spy.callCount; }, 'spy to be called', 100);

                runs(function() {
                    expect(spy).toHaveBeenCalled();
                });
            });
        });
    });

    describe("the layout", function() {
        beforeEach(function() {
            createTabPanel({
                layout: {
                    someConfig: 'something'
                }
            });
        });

        it("should be a card layout", function() {
            expect(tabPanel.layout instanceof Ext.layout.CardLayout).toBe(true);
        });

        it("should accept additional config", function() {
            expect(tabPanel.layout.someConfig).toEqual('something');
        });
    });

    describe("after initialization", function() {
        it("should have created a tab for each child component", function() {
            var count = 0;

            createTabPanelWithTabs(2);
            tabPanel.getTabBar().items.each(function(item) {
                if (item.is('tab')) {
                    count = count + 1;
                }
            });
            expect(count).toEqual(2);
        });

        describe('activeTab config', function() {
            it('if none, should set the first tab as active by default', function() {
                createTabPanelWithTabs(2);

                expect(tabPanel.getActiveTab()).toEqual(tabPanel.items.items[0]);
            });

            it('if undefined, should call setActiveTab with the correct item', function() {
                createTabPanelWithTabs(2, {
                    activeTab: undefined
                });

                expect(tabPanel.getActiveTab()).toEqual(tabPanel.items.items[0]);
            });

            it('if set, should call setActiveTab with the correct item', function() {
                createTabPanelWithTabs(2, {
                    activeTab: 1
                });

                expect(tabPanel.getActiveTab()).toEqual(tabPanel.items.items[1]);
            });

            it('if null, should not call setActiveTab', function() {
                createTabPanelWithTabs(2, {
                    activeTab: null
                });

                expect(tabPanel.getActiveTab()).toEqual(null);
            });

            it('if called when there are no tabs it should set activeTab as undefined', function() {
                createTabPanelWithTabs(0);
                tabPanel.setActiveTab(5);

                expect(tabPanel.getActiveTab()).toEqual(undefined);
                expect(tabPanel.activeTab).toBeUndefined();
            });
        });
    });

    describe("modifying items", function() {
        describe("tab configuration", function() {
            var tabBar;

            function addChild(config) {
                return tabPanel.add(Ext.apply({
                    xtype: 'panel',
                    title: 'new',
                    html: 'New Panel',
                    itemId: 'newItem'
                }, config));
            }

            beforeEach(function() {
                createTabPanel();

                tabBar = tabPanel.getTabBar();
            });

            it("should give the tab a reference to the card", function() {
                var newChild = addChild(),
                    newTab   = tabBar.items.first();

                expect(newTab.card).toEqual(newChild);
            });

            it("should give the tab a reference to the tabBar", function() {
                var newChild = addChild(),
                    newTab   = tabBar.items.first();

                expect(newTab.tabBar).toEqual(tabBar);
            });

            it("should not overwrite closeText with undefined", function() {
                var tab = addChild().tab;

                expect(tab.closeText).toBe('removable');
            });

            it("should overwrite closeText when specified in tab config", function() {
                var tab = addChild({ closeText: 'foo bar' }).tab;

                expect(tab.closeText).toBe('foo bar');
            });
        });

        it("should append a tab to the end", function() {
            createTabPanelWithTabs(3);
            var item = tabPanel.add({
                    title: 'foo'
                }),
                tab, items;

            items = tabPanel.getTabBar().items;
            tab = items.getAt(3);
            expect(items.getCount()).toBe(4);
            expect(tab.text).toBe('foo');
            expect(item.tab).toBe(tab);
        });

        it("should insert a tab at the specified index", function() {
            createTabPanelWithTabs(3);

            var item = tabPanel.insert(1, {
                    title: 'foo'
                }),
                items, tab;

            items = tabPanel.getTabBar().items;
            tab = items.getAt(1);
            expect(items.getCount()).toBe(4);
            expect(tab.text).toBe('foo');
            expect(item.tab).toBe(tab);
        });

        it("should move the tab when using moveBefore", function() {
            createTabPanelWithTabs(5);

            var item = tabPanel.down('#item1'),
                items, tab;

            tabPanel.moveBefore(item, tabPanel.down('#item4'));

            items = tabPanel.getTabBar().items;
            tab = items.getAt(2);
            expect(items.getCount()).toBe(5);
            expect(tab.text).toBe('test 1');
            expect(item.tab).toBe(tab);
        });

        it("should move the tab when using moveAfter", function() {
            createTabPanelWithTabs(5);

            var item = tabPanel.down('#item1'),
                items, tab;

            tabPanel.moveAfter(item, tabPanel.down('#item3'));

            items = tabPanel.getTabBar().items;
            tab = items.getAt(2);
            expect(items.getCount()).toBe(5);
            expect(tab.text).toBe('test 1');
            expect(item.tab).toBe(tab);
        });

        it("should remove the tab when removing", function() {
            createTabPanelWithTabs(3);

            tabPanel.remove(1);
            var items = tabPanel.getTabBar().items;

            expect(items.getCount()).toBe(2);
            expect(items.getAt(0).text).toBe('test 1');
            expect(items.getAt(1).text).toBe('test 3');
        });
    });

    describe("setting the active tab", function() {
        var waitForFocus = jasmine.waitForFocus,
            pressArrow = jasmine.pressArrowKey,
            expectFocused = jasmine.expectFocused,
            pressKey = jasmine.pressKey,
            item1, item2, item3, item4,
            tab1, tab2, tab3, tab4;

        beforeEach(function() {
            createTabPanelWithTabs(4);
            item1 = tabPanel.items.getAt(0);
            item2 = tabPanel.items.getAt(1);
            item3 = tabPanel.items.getAt(2);
            item4 = tabPanel.items.getAt(3);

            tab1 = item1.tab;
            tab2 = item2.tab;
            tab3 = item3.tab;
            tab4 = item4.tab;
        });

        afterEach(function() {
            tab1 = tab2 = tab3 = tab4 = null;
            item1 = item2 = item3 = item4 = null;
        });

        describe("programmatically", function() {
            describe("parameter types", function() {
                it("should accept a component index", function() {
                    tabPanel.setActiveTab(2);
                    expect(tabPanel.getActiveTab()).toBe(item3);
                });

                it("should accept an itemId", function() {
                    tabPanel.setActiveTab('item2');
                    expect(tabPanel.getActiveTab()).toBe(item2);
                });

                it("should accept an instance", function() {
                    tabPanel.setActiveTab('item2');
                    expect(tabPanel.getActiveTab()).toBe(tabPanel.down('#item2'));
                });

                it("should accept an object config and add it", function() {
                    tabPanel.setActiveTab({
                        itemId: 'item5'
                    });
                    expect(tabPanel.getActiveTab()).toBe(tabPanel.down('#item5'));
                });

                it("should leave the current active tab if the component is not found", function() {
                    tabPanel.setActiveTab(9);
                    expect(tabPanel.getActiveTab()).toBe(item1);
                });
            });

            describe("return value", function() {
                it("should return the current tab if the component could not be found", function() {
                    expect(tabPanel.setActiveTab(9)).toBe(item1);
                });

                it("should return the same tab if setting the current tab active", function() {
                    expect(tabPanel.setActiveTab(item1)).toBe(item1);
                });

                it("should return the current tab if the tab change is vetoed", function() {
                    tabPanel.on('beforetabchange', function() {
                        return false;
                    });
                    expect(tabPanel.setActiveTab(item3)).toBe(item1);
                });

                it("should return the new active item", function() {
                    expect(tabPanel.setActiveTab(item4)).toBe(item4);
                });

                it("should return a newly added item", function() {
                    var item = tabPanel.setActiveTab({
                        itemId: 'item5'
                    });

                    expect(item).toBe(tabPanel.down('#item5'));
                });
            });

            describe("events", function() {
                var beforeSpy, spy;

                beforeEach(function() {
                    beforeSpy = jasmine.createSpy();
                    spy = jasmine.createSpy();
                    tabPanel.on('beforetabchange', beforeSpy);
                    tabPanel.on('tabchange', spy);
                });

                afterEach(function() {
                    beforeSpy = spy = null;
                });

                describe("when the tab cannot be found", function() {
                    it("should not fire beforetabchange or tabchange", function() {
                        tabPanel.setActiveTab(9);
                        expect(beforeSpy).not.toHaveBeenCalled();
                        expect(spy).not.toHaveBeenCalled();
                    });
                });

                describe("when setting the same tab", function() {
                    it("should not fire beforetabchange or tabchange", function() {
                        tabPanel.setActiveTab(item1);
                        expect(beforeSpy).not.toHaveBeenCalled();
                        expect(spy).not.toHaveBeenCalled();
                    });
                });

                describe("when setting a new (existing) tab", function() {
                    it("should fire beforetabchange and pass the tabpanel, new tab & old tab", function() {
                        tabPanel.setActiveTab(item2);
                        expect(beforeSpy.callCount).toBe(1);
                        var args = beforeSpy.mostRecentCall.args;

                        expect(args[0]).toBe(tabPanel);
                        expect(args[1]).toBe(item2);
                        expect(args[2]).toBe(item1);
                    });

                    it("should not modify the activeTab if beforetabchange returns false", function() {
                        beforeSpy.andReturn(false);
                        tabPanel.setActiveTab(item2);
                        expect(spy).not.toHaveBeenCalled();
                        expect(tabPanel.getActiveTab()).toBe(item1);
                    });

                    it("should fire the tabchange event and pass the tabpanel, new tab & old tab", function() {
                        tabPanel.setActiveTab(item3);
                        expect(spy.callCount).toBe(1);
                        var args = spy.mostRecentCall.args;

                        expect(args[0]).toBe(tabPanel);
                        expect(args[1]).toBe(item3);
                        expect(args[2]).toBe(item1);
                    });
                });

                describe("when setting a new (config) tab", function() {
                    it("should pass the new item instance", function() {
                        tabPanel.setActiveTab({
                            itemId: 'item5'
                        });
                        var item = tabPanel.down('#item5');

                        expect(beforeSpy.mostRecentCall.args[1]).toBe(item);
                        expect(spy.mostRecentCall.args[1]).toBe(item);
                    });
                });

                describe("tab visibility", function() {
                    it("should not have the new tab visible when beforetabchange fires", function() {
                        var cardVisible, active;

                        beforeSpy.andCallFake(function() {
                            cardVisible = item2.isVisible();
                            active = tabPanel.getTabBar().activeTab;
                        });
                        tabPanel.setActiveTab(item2);
                        expect(cardVisible).toBe(false);
                        expect(active).toBe(item1.tab);
                    });

                    it("should have the new tab visible when tabchange fires", function() {
                        var cardVisible, active;

                        spy.andCallFake(function() {
                            cardVisible = item2.isVisible();
                            active = tabPanel.getTabBar().activeTab;
                        });
                        tabPanel.setActiveTab(item2);
                        expect(cardVisible).toBe(true);
                        expect(active).toBe(item2.tab);
                    });
                });

                it("should not affect layouts if beforetabchange is vetoed", function() {
                    var other = new Ext.Component({
                        renderTo: Ext.getBody(),
                        width: 50,
                        height: 50
                    });

                    tabPanel.on('beforetabchange', function() {
                        other.setSize(100, 100);

                        return false;
                    });

                    tabPanel.setActiveTab(item2);

                    expect(other.getWidth()).toBe(100);
                    expect(other.getHeight()).toBe(100);

                    other.destroy();
                });
            });

            describe("tab bar", function() {
                var bar;

                beforeEach(function() {
                    bar = tabPanel.getTabBar();
                });

                afterEach(function() {
                    bar = null;
                });

                it("should set the active item on the tab bar", function() {
                    tabPanel.setActiveTab(item2);
                    expect(bar.activeTab).toBe(item2.tab);
                });

                it("should not modify the tab bar item if the item cannot be found", function() {
                    tabPanel.setActiveTab(9);
                    expect(bar.activeTab).toBe(item1.tab);
                });

                it("should set the active item when adding a new item", function() {
                    tabPanel.setActiveTab({
                        itemId: 'item5'
                    });
                    expect(bar.activeTab).toBe(tabPanel.down('#item5').tab);
                });

                it("should not modify the tab bar item when beforetabchange returns false", function() {
                    tabPanel.on('beforetabchange', function() {
                        return false;
                    });
                    tabPanel.setActiveTab(item3);
                    expect(bar.activeTab).toBe(item1.tab);
                });
            });
        });

        describe("via the ui", function() {
            function clickTab(item) {
                runs(function() {
                    var target = item.tab.getEl().dom;

                    jasmine.fireMouseEvent(target, 'click');
                });

                // Need to yield enough cycles to unwind event handlers
                jasmine.waitAWhile();
            }

            function expectActiveItem(want) {
                runs(function() {
                    var have = tabPanel.getActiveTab();

                    expect(have).toBe(want);
                });
            }

            var beforeSpy, spy;

            beforeEach(function() {
                beforeSpy = jasmine.createSpy().andReturn(true);
                spy = jasmine.createSpy();
                tabPanel.on('beforetabchange', beforeSpy);
                tabPanel.on('tabchange', spy);
            });

            afterEach(function() {
                beforeSpy = spy = null;
            });

            describe("mouse", function() {
                describe("interaction", function() {
                    it("should set the active tab", function() {
                        clickTab(item2);

                        waitForFocus(tab2);

                        expectActiveItem(item2);
                    });

                    it("should not set the active tab if the beforetabchange event returns false", function() {
                        runs(function() {
                            beforeSpy.andReturn(false);
                        });

                        clickTab(item3);

                        expectActiveItem(item1);
                    });

                    it("should not set the active tab if the tab is disabled", function() {
                        item2.setDisabled(true);
                        clickTab(item2);
                        expectActiveItem(item1);
                    });
                });

                describe("focus handling", function() {
                    describe("during tab activate event", function() {
                        var textfield, tabFocusSpy, fieldFocusSpy;

                        beforeEach(function() {
                            tabFocusSpy   = jasmine.createSpy('tab focus');
                            fieldFocusSpy = jasmine.createSpy('textfield focus');

                            textfield = item3.add({
                                xtype: 'textfield',
                                listeners: {
                                    focus: fieldFocusSpy
                                }
                            });

                            item3.on('activate', function() {
                                // Give IE enough time to repaint the textfield,
                                // otherwise it won't properly focus but *will*
                                // fire the focus event, which results in repeatable
                                // but very confusing failures.
                                // ***PURE UNDILUTED HATRED***
                                jasmine.waitAWhile();

                                runs(function() {
                                    tab3.getFocusEl().on('focus', tabFocusSpy);
                                    textfield.focus();
                                });
                            });
                        });

                        it("should not force focus back to the tab", function() {
                            clickTab(item3);

                            waitForSpy(fieldFocusSpy);

                            // Unwind the handlers that could potentially refocus
                            jasmine.waitAWhile();

                            runs(function() {
                                expect(tabFocusSpy).not.toHaveBeenCalled();
                            });
                        });
                    });
                });

                describe("events", function() {
                    it("should fire no events if clicking on the active tab", function() {
                        clickTab(item1);

                        waitForFocus(tab1);

                        runs(function() {
                            expect(beforeSpy).not.toHaveBeenCalled();
                            expect(spy).not.toHaveBeenCalled();
                        });
                    });

                    it("should fire the beforetabchange event, passing the tab panel, new tab & old tab", function() {
                        clickTab(item2);

                        waitForFocus(tab2);

                        runs(function() {
                            expect(beforeSpy.callCount).toBe(1);
                            var args = beforeSpy.mostRecentCall.args;

                            expect(args[0]).toBe(tabPanel);
                            expect(args[1]).toBe(item2);
                            expect(args[2]).toBe(item1);
                        });
                    });

                    it("should fire the tabchange event, passing the tab panel, new tab & old tab", function() {
                        clickTab(item2);

                        waitForFocus(tab2);

                        runs(function() {
                            expect(spy.callCount).toBe(1);
                            var args = spy.mostRecentCall.args;

                            expect(args[0]).toBe(tabPanel);
                            expect(args[1]).toBe(item2);
                            expect(args[2]).toBe(item1);
                        });
                    });

                    it("should not fire the tabchange event if beforetabchange returns false", function() {
                        clickTab(item2);

                        waitForFocus(tab2);

                        runs(function() {
                            beforeSpy.andReturn(false);
                            spy = jasmine.createSpy();
                        });

                        clickTab(item3);

                        // Focus does change, but active tab does not
                        waitForFocus(tab3);

                        runs(function() {
                            expect(spy).not.toHaveBeenCalled();
                        });
                    });
                });
            });

            // Firefox and Safari on Mac will not focus <anchor> tags by default,
            // and that will make some or all tests below to fail. Fix TBD.
            var todoDescribe = (Ext.isMac && (Ext.isGecko || Ext.isSafari) ? xdescribe : describe);

            todoDescribe("keys", function() {
                describe("arrows", function() {
                    it("should go right from 1 to 2", function() {
                        pressArrow(tab1, 'right');

                        expectActiveItem(item2);
                    });

                    it("should loop over right from 4 to 1", function() {
                        pressArrow(tab4, 'right');

                        expectActiveItem(item1);
                    });

                    it("should go left from 2 to 1", function() {
                        pressArrow(tab2, 'left');

                        expectActiveItem(item1);
                    });

                    it("should loop over left to 4 from 1", function() {
                        pressArrow(tab1, 'left');

                        expectActiveItem(item4);
                    });
                });

                describe("Space/Enter", function() {
                    it("should activate card on Space key", function() {
                        pressKey(tab2, 'space');

                        expectActiveItem(item2);
                    });

                    it("should activate card on Enter key", function() {
                        pressKey(tab3, 'enter');

                        expectActiveItem(item3);
                    });
                });

                describe("activation", function() {
                    describe("activateOnFocus == true (default)", function() {
                        beforeEach(function() {
                            pressArrow(tab1, 'right');
                        });

                        it("should focus navigated-to tab", function() {
                            expectFocused(tab2);
                        });

                        it("should activate navigated-to item", function() {
                            expectActiveItem(item2);
                        });

                        it("should set active flag on navigated-to tab", function() {
                            runs(function() {
                                expect(tab1.active).toBeFalsy();
                                expect(tab2.active).toBe(true);
                            });
                        });

                        it("should not attempt to activate a child that is not a Tab", function() {
                            var button;

                            runs(function() {
                                button = new Ext.button.Button({
                                    text: 'foo',
                                    activate: jasmine.createSpy('activate')
                                });

                                tabPanel.getTabBar().insert(2, button);
                            });

                            pressArrow(tab2, 'right');

                            // Need to defer waitForFocus until the button is rendered
                            // so there is some DOM to wait for :)
                            runs(function() {
                                waitForFocus(button);
                            });

                            runs(function() {
                                expect(button.activate).not.toHaveBeenCalled();
                            });
                        });
                    });

                    describe("activateOnFocus == false", function() {
                        beforeEach(function() {
                            runs(function() {
                                tabPanel.getTabBar().setActivateOnFocus(false);
                            });

                            pressArrow(tab1, 'right');
                        });

                        it("should focus navigated-to tab", function() {
                            expectFocused(tab2);
                        });

                        it("should not activate navigated-to item", function() {
                            expectActiveItem(item1);
                        });

                        it("should not set active flag on navigated-to tab", function() {
                            runs(function() {
                                expect(tab1.active).toBe(true);
                                expect(tab2.active).toBeFalsy();
                            });
                        });
                    });
                });

                describe("events", function() {
                    // Focus by clicking before sending arrow key, so that
                    // events fire on the actual change. We have to click instead of
                    // just focusing because certain browsers (Firefox, I'm pointing at you!)
                    // do not fire focusing events correctly, we have to emulate these
                    // and things get messy real quick.
                    beforeEach(function() {
                        clickTab(item2);

                        waitForFocus(tab2);
                    });

                    describe("beforetabchange", function() {
                        beforeEach(function() {
                            pressArrow(tab2, 'right');

                            waitForFocus(tab3);
                        });

                        it("should fire the event", function() {
                            runs(function() {
                                // Extra call is when we're focusing the tab in beforeEach
                                expect(beforeSpy.callCount).toBe(2);
                            });
                        });

                        it("should pass the tab panel, new and old tab", function() {
                            runs(function() {
                                var args = beforeSpy.mostRecentCall.args;

                                expect(args[0]).toBe(tabPanel);
                                expect(args[1]).toBe(item3);
                                expect(args[2]).toBe(item2);
                            });
                        });
                    });

                    describe("tabchange", function() {
                        beforeEach(function() {
                            pressArrow(tab2, 'left');

                            waitForFocus(tab1);
                        });

                        it("should fire the event", function() {
                            runs(function() {
                                // Extra call is when we're focusing the tab in beforeEach
                                expect(spy.callCount).toBe(2);
                            });
                        });

                        it("should pass the tab panel, new and old tab", function() {
                            runs(function() {
                                var args = spy.mostRecentCall.args;

                                expect(args[0]).toBe(tabPanel);
                                expect(args[1]).toBe(item1);
                                expect(args[2]).toBe(item2);
                            });
                        });
                    });

                    describe("canceling beforetabchange", function() {
                        beforeEach(function() {
                            beforeSpy.andReturn(false);

                            // It was called when we focused tab2
                            spy = jasmine.createSpy();

                            pressArrow(tab2, 'right');

                            // Focus should change
                            waitForFocus(tab3);
                        });

                        it("should move focus to tab3", function() {
                            expectFocused(tab3);
                        });

                        it("should not fire tabchange event", function() {
                            expect(spy).not.toHaveBeenCalled();
                        });
                    });

                    describe("focusing non-Tab children", function() {
                        var button, beforeCount, changeCount;

                        beforeEach(function() {
                            runs(function() {
                                button = new Ext.button.Button({
                                    text: 'foo'
                                });

                                tabPanel.getTabBar().insert(2, button);

                                // Both spies are called when we change tabs above
                                beforeCount = beforeSpy.callCount;
                                changeCount = spy.callCount;
                            });

                            pressArrow(tab2, 'right');

                            runs(function() {
                                waitForFocus(button);
                            });
                        });

                        it("should not fire beforetabchange event", function() {
                            expect(beforeSpy.callCount).toBe(beforeCount);
                        });

                        it("should not fire tabchange event", function() {
                            expect(spy.callCount).toBe(changeCount);
                        });
                    });
                });
            });
        });
    });

    describe("removing child components", function() {
        it("should remove the corresponding tab from the tabBar", function() {
            createTabPanelWithTabs(2);

            var secondPanel = tabPanel.items.last(),
                tabBar      = tabPanel.getTabBar(),
                oldCount    = tabBar.items.length;

            tabPanel.remove(secondPanel);

            expect(tabBar.items.length).toEqual(oldCount - 1);
        });

        describe("if the removed child is the currently active tab", function() {
            var firstItem, secondItem, thirdItem;

            describe("and there is at least one tab after it", function() {
                beforeEach(function() {
                    createTabPanelWithTabs(3, { renderTo: document.body, activeTab: 1 });
                    firstItem  = tabPanel.items.first();
                    secondItem = tabPanel.items.getAt(1);
                    thirdItem = tabPanel.items.last();
                });

                it("should activate the next tab", function() {
                    // second is currently active
                    tabPanel.remove(secondItem);
                    expect(tabPanel.getActiveTab().title).toEqual(thirdItem.title);
                });
            });

            describe("and there is no tab before it but at least one after it", function() {
                beforeEach(function() {
                    createTabPanelWithTabs(2, { renderTo: document.body, activeTab: 0 });
                    firstItem  = tabPanel.items.items[0];
                    secondItem = tabPanel.items.items[1];
                });

                it("should activate the next tab", function() {
                    // first is currently active
                    tabPanel.remove(firstItem);
                    expect(tabPanel.getActiveTab().title).toEqual(secondItem.title);
                });
            });

            describe("and there are no other tabs", function() {
                beforeEach(function() {
                    createTabPanelWithTabs(1);
                    firstItem = tabPanel.items.first();
                });

                it("should not activate any other tabs", function() {
                    tabPanel.remove(firstItem);
                    expect(tabPanel.getActiveTab()).toBeUndefined();
                });
            });
        });
    });

    describe("AutoSizing", function() {
        beforeEach(function() {
            createTabPanel({
                width: 600,
                items: [{
                    title: '200 hi',
                    height: 200
                }, {
                    title: '400 hi',
                    height: 400
                }],
                renderTo: document.body
            });
        });

        it("should activate the first tab, and size to accommodate it", function() {
            expect(tabPanel.body.getViewSize().height).toBe(200);
        });
        it("should activate the second tab, and size to accommodate it", function() {
            tabPanel.setActiveTab(1);
            expect(tabPanel.body.getViewSize().height).toBe(400);
        });
    });

    xdescribe("TabPanel's minTabWidth/maxTabWidth", function() {
        it('Should create a single tab with width of 200', function() {
            createTabPanel({
                renderTo: document.body,
                minTabWidth: 200,
                items: {
                    title: 'Short'
                }
            });
            expect(tabPanel.down('tab').getWidth()).toBe(200);
        });
        it('Should create a single tab with width of 20', function() {
            createTabPanel({
                renderTo: document.body,
                maxTabWidth: 20,
                items: {
                    title: 'A very long title, but the tab must only be 20 wide'
                }
            });
            expect(tabPanel.down('tab').getWidth()).toBe(20);
        });
    });

    // Tests in this suite are about non-visual things, and rendering is disabled
    // to avoid odd layout failures in IE9m
    describe("ui", function() {
        it("should use the TabPanel's ui as the default UI for the Tab Bar and Tab", function() {
            createTabPanel({
                renderTo: undefined,
                ui: 'foo',
                items: [{ title: 'A' }]
            });

            expect(tabPanel.tabBar.ui).toBe('foo');
            expect(tabPanel.items.getAt(0).tab.ui).toBe('foo');
        });

        it("should use the Tab Bar's ui as the default UI for Tabs", function() {
            createTabPanel({
                renderTo: undefined,
                ui: 'foo',
                tabBar: {
                    ui: 'bar'
                },
                items: [{ title: 'A' }]
            });

            expect(tabPanel.tabBar.ui).toBe('bar');
            expect(tabPanel.items.getAt(0).tab.ui).toBe('bar');
        });

        it("should allow the tab to override the default UI", function() {
            createTabPanel({
                renderTo: undefined,
                ui: 'foo',
                tabBar: {
                    ui: 'bar'
                },
                items: [{
                    title: 'A',
                    tabConfig: {
                        ui: 'baz'
                    }
                }]
            });

            expect(tabPanel.tabBar.ui).toBe('bar');
            expect(tabPanel.items.getAt(0).tab.ui).toBe('baz');
        });
    });

    describe("bind", function() {
        it("should be able to bind the tab title when the view model is on the tab panel", function() {
            createTabPanel({
                renderTo: Ext.getBody(),
                viewModel: {
                    data: {
                        tab1: 'Foo',
                        tab2: 'Bar'
                    }
                },
                items: [{
                    bind: '{tab1}'
                }, {
                    bind: '{tab2}'
                }]
            });
            tabPanel.getViewModel().notify();
            var bar = tabPanel.getTabBar();

            expect(bar.items.first().getText()).toBe('Foo');
            expect(bar.items.last().getText()).toBe('Bar');
        });

        it("should be able to bind the tab title when the view model is on the tab item", function() {
            createTabPanel({
                renderTo: Ext.getBody(),
                items: [{
                    bind: '{title}',
                    viewModel: {
                        data: { title: 'Foo' }
                    }
                }, {
                    bind: '{title}',
                    viewModel: {
                        data: { title: 'Bar' }
                    }
                }]
            });
            var bar = tabPanel.getTabBar();

            tabPanel.items.first().getViewModel().notify();
            tabPanel.items.last().getViewModel().notify();
            expect(bar.items.first().getText()).toBe('Foo');
            expect(bar.items.last().getText()).toBe('Bar');
        });

        it("should not instance a view model inside an inactive tab when binding the title to a view model on the tab", function() {
            createTabPanel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Foo'
                }, {
                    bind: '{title}',
                    viewModel: {
                        data: { title: 'Bar' }
                    },
                    items: {
                        xtype: 'component',
                        viewModel: {
                            data: {}
                        }
                    }
                }]
            });
            var item = tabPanel.items.last();

            item.getViewModel().notify();
            expect(item.items.first().getConfig('viewModel', true).isViewModel).not.toBe(true);
        });
    });

    describe("removeAll", function() {
        it("should not activate any items", function() {
            var spy = jasmine.createSpy();

            tabPanel = new Ext.tab.Panel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Item 1'
                }, {
                    title: 'Item 2',
                    listeners: {
                        activate: spy
                    }
                }, {
                    title: 'Item 3',
                    listeners: {
                        activate: spy
                    }
                }]
            });
            tabPanel.removeAll();
            expect(spy).not.toHaveBeenCalled();
        });

        it("should not render any items during destroy", function() {
            var spy = jasmine.createSpy();

            tabPanel = new Ext.tab.Panel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Item 1'
                }, {
                    title: 'Item 2',
                    listeners: {
                        afterrender: spy
                    }
                }, {
                    title: 'Item 3',
                    listeners: {
                        afterrender: spy
                    }
                }]
            });
            tabPanel.removeAll();
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe("cleanup", function() {
        it("should leave no orphans", function() {
            var count = Ext.ComponentManager.getCount();

            tabPanel = new Ext.tab.Panel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Item 1',
                    closable: true
                }, {
                    title: 'Item 2',
                    closable: true
                }, {
                    title: 'Item 3',
                    closable: true
                }]
            });
            tabPanel.destroy();
            expect(Ext.ComponentManager.getCount()).toBe(count);
        });

        it("should not activate any items during destroy", function() {
            var spy = jasmine.createSpy();

            tabPanel = new Ext.tab.Panel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Item 1'
                }, {
                    title: 'Item 2',
                    listeners: {
                        activate: spy
                    }
                }, {
                    title: 'Item 3',
                    listeners: {
                        activate: spy
                    }
                }]
            });
            tabPanel.destroy();
            expect(spy).not.toHaveBeenCalled();
        });

        it("should not render any items during destroy", function() {
            var spy = jasmine.createSpy();

            tabPanel = new Ext.tab.Panel({
                renderTo: Ext.getBody(),
                items: [{
                    title: 'Item 1'
                }, {
                    title: 'Item 2',
                    listeners: {
                        afterrender: spy
                    }
                }, {
                    title: 'Item 3',
                    listeners: {
                        afterrender: spy
                    }
                }]
            });
            tabPanel.destroy();
            expect(spy).not.toHaveBeenCalled();
        });
    });

    describe('Loader', function() {
        var panel;

        function createPanel(cfg) {
            panel = createTabPanel(Ext.apply({
                renderTo: document.body,
                deferredRender: false,
                items: [{
                    title: 'Tab 1'
                }, {
                    title: 'Tab 2'
                }],
                loader: {
                    url: 'url',
                    renderer: 'component'
                }
            }, cfg || {}));
        }

        function mockComplete(responseText, status) {
            Ext.Ajax.mockComplete({
                status: status || 200,
                responseText: responseText || 'response'
            });
        }

        beforeEach(function() {
            MockAjaxManager.addMethods();
        });

        afterEach(function() {
            panel.destroy();
            panel = null;
            MockAjaxManager.removeMethods();
        });

        it('should add to the number of tabs', function() {
            createPanel();
            tabPanel.loader.load();
            mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');
            expect(tabPanel.tabBar.items.length).toEqual(4);
        });

        describe('setActiveTab', function() {
            describe('pre-existing tabs', function() {
                it('should not call setActiveTab', function() {
                    createPanel();
                    spyOn(tabPanel, 'setActiveTab');

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(tabPanel.setActiveTab).not.toHaveBeenCalled();
                });

                it('should not call setActiveTab when activeItem is null', function() {
                    createPanel({
                        activeTab: null
                    });
                    spyOn(tabPanel, 'setActiveTab');

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(tabPanel.setActiveTab).not.toHaveBeenCalled();
                });
            });

            describe('no pre-existing tabs', function() {
                it('should call setActiveTab', function() {
                    createPanel({
                        items: null
                    });

                    spyOn(tabPanel, 'setActiveTab');

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');

                    expect(tabPanel.tabBar.items.length).toEqual(2);
                    expect(tabPanel.setActiveTab).toHaveBeenCalled();
                });

                it('should not call setActiveTab when activeItem is null', function() {
                    createPanel({
                        activeTab: null,
                        items: null
                    });
                    spyOn(tabPanel, 'setActiveTab');

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(tabPanel.setActiveTab).not.toHaveBeenCalled();
                });
            });

            describe('during a pending load', function() {
                // See EXTJS-16054.
                beforeEach(function() {
                    createPanel({
                        items: null
                    });

                    tabPanel.setActiveTab(1);
                });

                it('should not set the activeTab as null', function() {
                    expect(tabPanel.getActiveTab()).not.toBeNull();

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');
                });

                it('should set the activeTab as undefined', function() {
                    expect(tabPanel.getActiveTab()).toBeUndefined();

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');
                });

                it('should set a default tab as active when load returns', function() {
                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');

                    expect(tabPanel.getActiveTab()).toBeDefined();
                });
            });
        });

        describe('loading new tabs', function() {
            it('should not set an active item when activeItem is null (pre-existing tabs)', function() {
                createPanel({
                    activeTab: null
                });

                tabPanel.loader.load();
                mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                // expect(tabPanel.layout.getActiveItem()).toBe(null);
                expect(tabPanel.layout.getActiveItem() === null).toBe(true);
            });

            it('should not set an active item when activeItem is null (no pre-existing tabs)', function() {
                createPanel({
                    activeTab: null,
                    items: null
                });

                tabPanel.loader.load();
                mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');

                expect(tabPanel.layout.getActiveItem() === null).toBe(true);
            });
        });

        describe('tabchange event', function() {
            var called = false;

            afterEach(function() {
                called = false;
            });

            describe('pre-existing tabs', function() {
                it('should not fire', function() {
                    createPanel();

                    tabPanel.on('tabchange', function() {
                        called = true;
                    }, this);

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(called).toBe(false);
                });

                it('should not fire when activeItem is null', function() {
                    createPanel({
                        activeTab: null
                    });

                    tabPanel.on('tabchange', function() {
                        called = true;
                    }, this);

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(called).toBe(false);
                });
            });

            describe('no pre-existing tabs', function() {
                it('should fire', function() {
                    createPanel({
                        items: null
                    });

                    tabPanel.on('tabchange', function() {
                        called = true;
                    }, this);

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 1"}, {"title": "Tab 2"}]');

                    expect(tabPanel.tabBar.items.length).toEqual(2);
                    expect(called).toBe(true);
                });

                it('should not fire when activeItem is null', function() {
                    createPanel({
                        activeTab: null,
                        items: null
                    });

                    tabPanel.on('tabchange', function() {
                        called = true;
                    }, this);

                    tabPanel.loader.load();
                    mockComplete('[{"title": "Tab 3"}, {"title": "Tab 4"}]');

                    expect(called).toBe(false);
                });
            });
        });
    });

    describe("tabRotation", function() {
        it("should not override the rotation config of indiviual tabs", function() {
            createTabPanel({
                tabRotation: 2,
                items: [{
                    tabConfig: {
                        rotation: 0
                    }
                }]
            });

            expect(tabPanel.tabBar.items.getAt(0).rotation).toBe(0);
        });

        it("should not override the rotation config of indiviual tabs, when tabPosition is configured", function() {
            createTabPanel({
                tabPosition: 'right',
                items: [{
                    tabConfig: {
                        rotation: 0
                    }
                }]
            });

            expect(tabPanel.tabBar.items.getAt(0).rotation).toBe(0);
        });

        it("should allow rotation to be configured on the tabBar", function() {
            createTabPanelWithTabs(1, {
                tabBar: {
                    tabRotation: 2
                }
            });
            expect(tabPanel.tabBar.items.getAt(0).rotation).toBe(2);
        });

        it("should pass configured tabRotation on to the tabs", function() {
            createTabPanelWithTabs(1, {
                tabRotation: 2
            });
            expect(tabPanel.tabBar.items.getAt(0).rotation).toBe(2);
        });

        describe("default behavior", function() {
            it("should not have rotation classes for tabPosition:top", function() {
                createTabPanelWithTabs(1);
                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-left');
                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-right');
            });

            it("should default to 0 for tabPosition:bottom", function() {
                createTabPanelWithTabs(1, {
                    tabPosition: 'bottom'
                });

                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-left');
                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-right');
            });

            it("should have right rotation cls for tabPosition:right", function() {
                createTabPanelWithTabs(1, {
                    tabPosition: 'right'
                });

                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-left');
                expect(tabPanel.tabBar.items.getAt(0).el).toHaveCls('x-tab-rotate-right');
            });

            it("should have left rotation cls for tabPosition:left", function() {
                createTabPanelWithTabs(1, {
                    tabPosition: 'left'
                });

                expect(tabPanel.tabBar.items.getAt(0).el).toHaveCls('x-tab-rotate-left');
                expect(tabPanel.tabBar.items.getAt(0).el).not.toHaveCls('x-tab-rotate-right');
            });
        });
    });

    describe("tabBarHeaderPosition", function() {
        it("should render the tabBar as a docked item if tabBarHeaderPosition is unspecified", function() {
            createTabPanelWithTabs(1);
            expect(tabPanel.getDockedItems()[0]).toBe(tabPanel.tabBar);
        });

        it("should render the tabBar as a header item if tabBarHeaderPosition is specified", function() {
            createTabPanelWithTabs(1, {
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.getDockedItems().length).toBe(1);
            expect(tabPanel.getDockedItems()[0]).toBe(tabPanel.header);
            expect(tabPanel.tabBar.ownerCt).toBe(tabPanel.header);
        });

        it("should render the tabBar before the title", function() {
            createTabPanelWithTabs(1, {
                ariaRole: 'tabpanel',
                title: 'Foo',
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.getDockedItems().length).toBe(1);
            expect(tabPanel.getDockedItems()[0]).toBe(tabPanel.header);
            expect(tabPanel.tabBar.ownerCt).toBe(tabPanel.header);
            expect(tabPanel.header.items.getAt(0)).toBe(tabPanel.tabBar);
            expect(tabPanel.header.items.getAt(1)).toBe(tabPanel.header.getTitle());
        });

        it("should render the tabBar after the title", function() {
            createTabPanelWithTabs(1, {
                ariaRole: 'tabpanel',
                title: 'Foo',
                tabBarHeaderPosition: 1
            });
            expect(tabPanel.getDockedItems().length).toBe(1);
            expect(tabPanel.getDockedItems()[0]).toBe(tabPanel.header);
            expect(tabPanel.tabBar.ownerCt).toBe(tabPanel.header);
            expect(tabPanel.header.items.getAt(0)).toBe(tabPanel.header.getTitle());
            expect(tabPanel.header.items.getAt(1)).toBe(tabPanel.tabBar);
        });

        it("should default the tabBar's 'dock' config to 'top' when inside a top header", function() {
            createTabPanelWithTabs(1, {
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.tabBar.dock).toBe('top');
        });

        it("should default the tabBar's 'dock' config to 'right' when inside a right header", function() {
            createTabPanelWithTabs(1, {
                headerPosition: 'right',
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.tabBar.dock).toBe('right');
        });

        it("should default the tabBar's 'dock' config to 'bottom' when inside a bottom header", function() {
            createTabPanelWithTabs(1, {
                headerPosition: 'bottom',
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.tabBar.dock).toBe('bottom');
        });

        it("should default the tabBar's 'dock' config to 'left' when inside a left header", function() {
            createTabPanelWithTabs(1, {
                headerPosition: 'left',
                tabBarHeaderPosition: 0
            });
            expect(tabPanel.tabBar.dock).toBe('left');
        });

        it("should render the tabBar after any existing header items", function() {
            createTabPanelWithTabs(1, {
                header: {
                    items: [{
                        xtype: 'button',
                        text: 'hi'
                    }]
                },
                tabBarHeaderPosition: 0
            });

            expect(tabPanel.header.items.getAt(0) instanceof Ext.button.Button).toBe(true);
            expect(tabPanel.header.items.getAt(1)).toBe(tabPanel.tabBar);
        });

        it("should not mutate the header config", function() {
            var headerCfg = {
                title: 'Foo',
                items: [{
                    xtype: 'button',
                    text: 'hi'
                }]
            };

            createTabPanelWithTabs(1, {
                header: headerCfg,
                tabBarHeaderPosition: 1
            });
            expect(headerCfg.items.length).toBe(1);
            expect(tabPanel.header.items.getCount()).toBe(3);
        });
    });

    describe("tabPosition", function() {
        it("should dock the tabBar to the top when tabPosition is 'top'", function() {
            createTabPanelWithTabs(1);
            expect(tabPanel.tabBar.dock).toBe('top');
        });

        it("should dock the tabBar to the right when tabPosition is 'right'", function() {
            createTabPanelWithTabs(1, {
                tabPosition: 'right'
            });
            expect(tabPanel.tabBar.dock).toBe('right');
        });

        it("should dock the tabBar to the bottom when tabPosition is 'bottom'", function() {
            createTabPanelWithTabs(1, {
                tabPosition: 'bottom'
            });
            expect(tabPanel.tabBar.dock).toBe('bottom');
        });

        it("should dock the tabBar to the left when tabPosition is 'left'", function() {
            createTabPanelWithTabs(1, {
                tabPosition: 'left'
            });
            expect(tabPanel.tabBar.dock).toBe('left');
        });

        it("should ignore tabPosition when tabBarHeaderPosition is specified", function() {
            createTabPanelWithTabs(1, {
                tabPosition: 'left',
                headerPosition: 'bottom',
                tabBarHeaderPosition: 0
            });

            expect(tabPanel.tabBar.dock).toBe('bottom');
        });

        it("should set tabPosition after rendering", function() {
            createTabPanelWithTabs(1);

            tabPanel.setTabPosition('left');
            expect(tabPanel.tabBar.dock).toBe('left');
        });

        it("should not allow setting of tabPosition after rendering if tabBarHeaderPosition was specified", function() {
            createTabPanelWithTabs(1, {
                headerPosition: 'bottom',
                tabBarHeaderPosition: 0
            });

            tabPanel.setTabPosition('left');
            expect(tabPanel.tabBar.dock).toBe('bottom');
        });
    });

    describe("enable/disable", function() {
        beforeEach(function() {
            createTabPanelWithTabs(2, {
                activeTab: 1,
                disabled: true
            });
        });

        it("should activate tab when enabled", function() {
            tabPanel.enable();

            expect(tabPanel.tabBar.activeTab.card.itemId).toBe('item2');
        });
    });

    describe("ARIA", function() {
        var tab1, tab2, card1, card2;

        beforeEach(function() {
            createTabPanelWithTabs(2);

            card1 = tabPanel.items.getAt(0);
            card2 = tabPanel.items.getAt(1);

            tab1 = card1.tab;
            tab2 = card2.tab;
        });

        afterEach(function() {
            tab1 = tab2 = card1 = card2 = null;
        });

        describe("attributes", function() {
            it("should have tab role on the tab", function() {
                expect(tab1).toHaveAttr('role', 'tab');
            });

            it("should have tabpanel role on the card", function() {
                expect(card1).toHaveAttr('role', 'tabpanel');
            });

            it("should have aria-selected='true' on tab1", function() {
                expect(tab1).toHaveAttr('aria-selected', 'true');
            });

            it("should have aria-selected='false' on tab2", function() {
                expect(tab2).toHaveAttr('aria-selected', 'false');
            });

            it("should have aria-labelledby on card1", function() {
                expect(card1).toHaveAttr('aria-labelledby', tab1.id);
            });

            it("should not have aria-label on card1", function() {
                expect(card1).not.toHaveAttr('aria-label');
            });

            it("should have aria-expanded='true' on card1", function() {
                expect(card1).toHaveAttr('aria-expanded', 'true');
            });

            it("should have aria-hidden='false' on card1", function() {
                expect(card1).toHaveAttr('aria-hidden', 'false');
            });

            describe("dynamically added panel", function() {
                var tab3, card3;

                beforeEach(function() {
                    card3 = tabPanel.add(new Ext.panel.Panel({
                        title: '<span style="background-color: red">foo</span>',
                        html: 'blerg'
                    }));

                    tab3 = card3.tab;

                    // This is to render the tab child
                    tabPanel.setActiveTab(2);
                });

                afterEach(function() {
                    tab3 = card3 = null;
                });

                it("should have correct aria-labelledby on card1", function() {
                    expect(card3).toHaveAttr('aria-labelledby', tab3.id);
                });

                it("should not have aria-label on card1", function() {
                    expect(card3).not.toHaveAttr('aria-label');
                });
            });

            describe("dynamically moved panel", function() {
                var tabPanel2, oldTab1Id;

                beforeEach(function() {
                    tabPanel2 = new Ext.tab.Panel({
                        renderTo: Ext.getBody()
                    });

                    oldTab1Id = tab1.id;

                    tabPanel.remove(card1, false);
                    tabPanel2.add(card1);

                    tab1 = card1.tab;
                });

                afterEach(function() {
                    tabPanel2.destroy();
                    tabPanel2 = oldTab1Id = null;
                });

                it("should have new tab id on card1", function() {
                    expect(tab1.id).not.toBe(oldTab1Id);
                });

                it("should have correct aria-labelledby on card1", function() {
                    expect(card1).toHaveAttr('aria-labelledby', tab1.id);
                });

                it("should not have aria-label on card1", function() {
                    expect(card1).not.toHaveAttr('aria-label');
                });
            });
        });

        describe("tab switching", function() {
            beforeEach(function() {
                tabPanel.setActiveTab(1);
            });

            describe("aria-selected", function() {
                it("should be true on tab2", function() {
                    expect(tab2).toHaveAttr('aria-selected', 'true');
                });

                it("should be false on tab1", function() {
                    expect(tab1).toHaveAttr('aria-selected', 'false');
                });
            });

            describe("aria-expanded", function() {
                it("should be true on card2", function() {
                    expect(card2).toHaveAttr('aria-expanded', 'true');
                });

                it("should be false on card1", function() {
                    expect(card1).toHaveAttr('aria-expanded', 'false');
                });
            });

            describe("aria-hidden", function() {
                it("should be true on card1", function() {
                    expect(card1).toHaveAttr('aria-hidden', 'true');
                });

                it("should be false on card2", function() {
                    expect(card2).toHaveAttr('aria-hidden', 'false');
                });
            });
        });
    });

    describe("layout counts", function() {
        it("should only do a single layout when removing the active tab", function() {
            createTabPanelWithTabs(2);
            var cnt = tabPanel.componentLayoutCounter;

            tabPanel.items.first().destroy();
            expect(tabPanel.componentLayoutCounter - cnt).toBe(1);
        });
    });
});
