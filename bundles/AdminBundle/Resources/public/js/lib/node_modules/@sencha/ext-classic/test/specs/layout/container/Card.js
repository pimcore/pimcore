topSuite("Ext.layout.container.Card",
    ['Ext.grid.Panel', 'Ext.tab.Panel', 'Ext.layout.container.Border'],
function() {
    var comp;

    function createCardContainer(config) {
        comp = Ext.widget(Ext.apply({
            xtype: config.xtype || 'container',
            width: 100,
            height: 100,
            layout: {
                type: 'card',
                deferredRender: config.deferredRender
            },
            renderTo: document.body
        }, config));

        return comp;
    }

    function makeCard(text) {
        return new Ext.panel.Panel({
            title: text || 'Loooooooong text',
            scrollable: true,
            items: {
                xtype: 'box',
                html: makeText()
            }
        });
    }

    function makeData(len) {
        var res = [],
            i;

        for (i = 0, len = len || 100; i < len; i++) {
            res.push({
                id: i,
                name: 'foo_' + i
            });
        }

        return res;
    }

    function makeText() {
        var text = [],
            i;

        for (i = 0; i < 7000; i++) {
            text.push('The Owl House');
        }

        return text.join('');
    }

    afterEach(function() {
        comp = Ext.destroy(comp);
    });

    describe("alternate class name", function() {
        it("should have Ext.layout.CardLayout as the alternate class name", function() {
            expect(Ext.layout.container.Card.prototype.alternateClassName).toEqual("Ext.layout.CardLayout");
        });

        it("should allow the use of Ext.layout.CardLayout", function() {
            expect(Ext.layout.CardLayout).toBeDefined();
        });
    });

    describe("visibility", function() {
        it("should have the active item as visible", function() {
            createCardContainer({
                defaultType: 'component',
                items: [{
                    itemId: 'a'
                }, {
                    itemId: 'b'
                }]
            });
            expect(comp.down('#a').isVisible()).toBe(true);
        });

        it("should have the inactive items as not visible", function() {
            createCardContainer({
                defaultType: 'component',
                items: [{
                    itemId: 'a'
                }, {
                    itemId: 'b'
                }, {
                    itemId: 'c'
                }]
            });
            expect(comp.down('#b').isVisible()).toBe(false);
            expect(comp.down('#c').isVisible()).toBe(false);
        });

        it("should have child items of inactive items as not visible with deep: true", function() {
            createCardContainer({
                items: [{
                    xtype: 'component',
                    itemId: 'a'
                }, {
                    xtype: 'container',
                    items: {
                        xtype: 'component',
                        itemId: 'b'
                    }
                }, {
                    xtype: 'container',
                    items: {
                        xtype: 'component',
                        itemId: 'c'
                    }
                }]
            });
            expect(comp.down('#b').isVisible(true)).toBe(false);
            expect(comp.down('#c').isVisible(true)).toBe(false);
        });

        // Tests EXTJS-15545
        describe("with an added listener", function() {
            it("should have child items of inactive items as not visible with deep: true", function() {
                createCardContainer({
                    items: [{
                        xtype: 'component',
                        itemId: 'a'
                    }, {
                        xtype: 'container',
                        listeners: { added: function() {} },
                        items: {
                            xtype: 'component',
                            itemId: 'b'
                        }
                    }, {
                        xtype: 'container',
                        listeners: { added: function() {} },
                        items: {
                            xtype: 'component',
                            itemId: 'c'
                        }
                    }]
                });
                expect(comp.down('#b').isVisible(true)).toBe(false);
                expect(comp.down('#c').isVisible(true)).toBe(false);
            });
        });
    });

    describe("Sizing", function() {
        it("should size the child using both dimensions", function() {
            createCardContainer({
                items: {
                    xtype: 'component'
                }
            });
            expect(comp.items.items[0].getWidth()).toEqual(100);
            expect(comp.items.items[0].getHeight()).toEqual(100);
        });

        it("should size the child using height and shrinkWrap width", function() {
            createCardContainer({
                height: 100,
                width: undefined,
                style: 'position:absolute', // Avoid the 100% body width and allow the shrinkWrap
                items: {
                    xtype: 'component',
                    width: 200
                }
            });
            expect(comp.items.items[0].getHeight()).toEqual(100);
            expect(comp.getWidth()).toEqual(200);
        });

        it("should size the child using width and shrinkWrap height", function() {
            createCardContainer({
                width: 100,
                height: undefined,
                items: {
                    xtype: 'component',
                    height: 200
                }
            });
            expect(comp.items.items[0].getWidth()).toEqual(100);
            expect(comp.getHeight()).toEqual(200);
        });
    });

    describe('Deferred render', function() {
        it("should render all children", function() {
            createCardContainer({
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component'
                }]
            });
            expect(comp.items.items[0].el).toBeDefined();
            expect(comp.items.items[1].el).toBeDefined();
        });

        it("should only render the active item", function() {
            createCardContainer({
                activeItem: 1,
                deferredRender: true,
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component'
                }]
            });
            expect(comp.items.items[0].el).toBeUndefined();
            expect(comp.items.items[1].el).toBeDefined();
        });
    });

    describe('Events', function() {
        it("should fire beforeactivate and activate on item 1", function() {
            var comp1BeforeActivated, comp1Activated;

            createCardContainer({
                activeItem: 1,
                deferredRender: true,
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component',
                    listeners: {
                        beforeactivate: function() {
                            comp1BeforeActivated = true;
                        },
                        activate: function() {
                            comp1Activated = true;
                        }
                    }
                }]
            });
            expect(comp1BeforeActivated).toEqual(true);
            expect(comp1Activated).toEqual(true);
            expect(comp.items.items[0].el).toBeUndefined();
            expect(comp.items.items[1].el).toBeDefined();
        });

        it("should veto activation of item 1", function() {
            var comp1BeforeActivated, comp1Activated;

            createCardContainer({
                activeItem: 1,
                deferredRender: true,
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component',
                    listeners: {
                        beforeactivate: function() {
                            comp1BeforeActivated = true;

                            return false;
                        },
                        activate: function() {
                            comp1Activated = true;
                        }
                    }
                }]
            });
            expect(comp1BeforeActivated).toEqual(true);
            expect(comp1Activated).toBeUndefined();
            expect(comp.items.items[0].el).toBeUndefined();
            expect(comp.items.items[1].el).toBeUndefined();
        });
    });

    describe('Active Item', function() {
        describe('calling getActiveItem()', function() {
            it('should return a default item when activeItem is set', function() {
                comp = createCardContainer({
                    activeItem: 0,
                    items: {
                        xtype: 'component'
                    }
                });

                expect(comp.layout.getActiveItem()).toBe(comp.items.items[0]);
            });

            it('should return a default item if activeItem is not defined', function() {
                comp = createCardContainer({
                    items: {
                        xtype: 'component'
                    }
                });

                expect(comp.layout.getActiveItem()).toBe(comp.items.items[0]);
            });

            it('should return a default item if activeItem is undefined', function() {
                comp = createCardContainer({
                    activeItem: undefined,
                    items: {
                        xtype: 'component'
                    }
                });

                expect(comp.layout.getActiveItem()).toBe(comp.items.items[0]);
            });

            it('should not return a default item if activeItem is null', function() {
                comp = createCardContainer({
                    activeItem: null,
                    items: {
                        xtype: 'component'
                    }
                });

                expect(comp.layout.getActiveItem()).toBe(null);
            });

            it('should return the specified active item', function() {
                comp = createCardContainer({
                    activeItem: 1,
                    items: [{
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }]
                });

                expect(comp.layout.getActiveItem()).toBe(comp.items.items[1]);
            });

            it('should return the specified active item if deferred render', function() {
                comp = createCardContainer({
                    activeItem: 1,
                    deferredRender: true,
                    items: [{
                        xtype: 'component'
                    }, {
                        xtype: 'component'
                    }]
                });

                expect(comp.layout.getActiveItem()).toBe(comp.items.items[1]);
            });
        });

        it('should display the first item as active item when active item is not set', function() {
            var items, item0;

            comp = createCardContainer({
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component'
                }]
            });

            items = comp.items;
            item0 = items.getAt(0);

            expect(item0.hidden).toBe(false);
            expect(items.getAt(1).hidden).toBe(true);

            expect(comp.layout.getActiveItem()).toBe(item0);
        });

        it('should not display any item as active item when active item is null', function() {
            var items, item0;

            comp = createCardContainer({
                activeItem: null,
                items: [{
                    xtype: 'component'
                }, {
                    xtype: 'component'
                }]
            });

            items = comp.items;

            expect(items.getAt(0).hidden).toBe(true);
            expect(items.getAt(1).hidden).toBe(true);

            expect(comp.layout.getActiveItem()).toBe(null);
        });
    });

    describe('scroll position when changing cards', function() {
        var c, scrollable;

        function removeCard() {
            var layout = comp.layout,
                c = layout.getActiveItem();

            layout.prev();
            c.destroy();
        }

        afterEach(function() {
            c = scrollable = null;
        });

        it('should not inherit old scroll positions from previously deleted cards', function() {
            // This may seem odd, but there's a FF bug that will preserve the scroll position
            // of a destroyed card and reapply it to the next created one.
            // See EXTJS-16173.
            createCardContainer({
                items: [makeCard()]
            });

            // Create a new card.
            c = comp.layout.setActiveItem(makeCard());
            // Scroll it.
            c.getScrollable().scrollTo(0, 10000);
            // Remove it and set the previous card as active.
            removeCard();
            // Create a new card and check its scroll position.
            c = comp.layout.setActiveItem(makeCard());

            expect(c.getScrollable().getElement().dom.scrollTop).toBe(0);
        });

        describe('preserving scroll position', function() {
            // See EXTJS-17978.
            var scrollPosition;

            afterEach(function() {
                scrollPosition = null;
            });

            describe('when card items are not nested', function() {
                it('should keep scroll position, simple layout', function() {
                    createCardContainer({
                        items: [makeCard('Foo'), {
                            title: "The Owl's Nest Farm"
                        }]
                    });

                    c = comp.items.getAt(0);
                    scrollable = c.getScrollable();
                    scrollable.scrollTo(0, 2000);
                    scrollPosition = scrollable.getPosition();

                    // Toggle.
                    comp.setActiveItem(1);
                    comp.setActiveItem(0);

                    expect(scrollable.getPosition().y).toBe(scrollPosition.y);
                });
            });

            describe('when card items are nested', function() {
                it('should keep scroll position, simple layout', function() {
                    createCardContainer({
                        items: [{
                            title: 'The Owl House',
                            xtype: 'container',
                            scrollable: true,
                            items: [{
                                id: 'BT',
                                xtype: 'component',
                                scrollable: true,
                                height: 300,
                                html: makeText()

                            }]
                        }, makeCard()]
                    });

                    c = Ext.getCmp('BT');
                    scrollable = c.getScrollable();
                    scrollable.scrollTo(0, 2000);
                    scrollPosition = scrollable.getPosition();

                    // Toggle.
                    comp.setActiveItem(1);
                    comp.setActiveItem(0);

                    expect(scrollable.getPosition().y).toBe(scrollPosition.y);
                });
            });

            describe('when items are derived panels', function() {
                var synchronousLoad = true,
                    proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
                    loadStore = function() {
                        proxyStoreLoad.apply(this, arguments);

                        if (synchronousLoad) {
                            this.flushLoad.apply(this, arguments);
                        }

                        return this;
                    };

                beforeEach(function() {
                    // Override so that we can control asynchronous loading
                    Ext.data.ProxyStore.prototype.load = loadStore;
                });

                afterEach(function() {
                    // Undo the overrides.
                    Ext.data.ProxyStore.prototype.load = proxyStoreLoad;
                });

                function doTest(isBuffered, isLocked) {
                    describe('buffered = ' + isBuffered + ', locked = ' + isLocked, function() {
                        it('should keep scroll position, simple layout', function() {
                            createCardContainer({
                                height: 300,
                                width: 300,
                                items: [{
                                    xtype: 'grid',
                                    buffered: isBuffered,
                                    columns: [{
                                        text: 'Id',
                                        dataIndex: 'id',
                                        width: 100
                                    }, {
                                        text: 'Name',
                                        dataIndex: 'name',
                                        locked: isLocked,
                                        width: 100
                                    }],
                                    store: {
                                        fields: ['id', 'name'],
                                        data: makeData(10000)
                                    }
                                }, {
                                    title: "The Owl's Nest Farm"
                                }]
                            });

                            c = comp.items.getAt(0);
                            scrollable = isLocked ? c.normalGrid.view.scrollable : c.view.scrollable;
                            scrollable.scrollTo(0, 7000);
                            scrollPosition = scrollable.getPosition();

                            // Toggle.
                            comp.setActiveItem(1);
                            comp.setActiveItem(0);

                            expect(scrollable.getPosition().y).toBe(scrollPosition.y);
                        });

                        it('should keep scroll position, complex layout', function() {
                            var p;

                            createCardContainer({
                                xtype: 'tabpanel',
                                region: 'west',
                                height: 300,
                                width: 300,
                                collapsible: true,
                                collapsed: false,
                                renderTo: null,
                                items: [{
                                    xtype: 'grid',
                                    title: 'The grid to end all grids',
                                    buffered: isBuffered,
                                    columns: [{
                                        text: 'Id',
                                        dataIndex: 'id',
                                        width: 100
                                    }, {
                                        text: 'Name',
                                        dataIndex: 'name',
                                        locked: isLocked,
                                        width: 100
                                    }],
                                    store: {
                                        fields: ['id', 'name'],
                                        data: makeData(10000)
                                    }
                                }, {
                                    title: "The Owl's Nest Farm"
                                }]
                            });

                            p = new Ext.panel.Panel({
                                width: 1000,
                                height: 700,
                                title: 'Border Layout',
                                layout: 'border',
                                items: [{
                                    region: 'center',
                                    xtype: 'panel',
                                    margin: '5 0 0 5',
                                    layout: 'fit'
                                }, comp],
                                renderTo: Ext.getBody()
                            });

                            c = comp.items.getAt(0);
                            scrollable = isLocked ? c.normalGrid.view.scrollable : c.view.scrollable;
                            scrollable.scrollTo(0, 7000);
                            scrollPosition = scrollable.getPosition();

                            // Toggle.
                            comp.setActiveTab(1);

                            // Now toggle the state of the west region.
                            comp.down('tool').scope.collapse(null, false);
                            comp.down('tool').scope.expand(null, false);

                            comp.setActiveTab(0);

                            expect(scrollable.getPosition().y).toBe(scrollPosition.y);

                            p = Ext.destroy(p);
                        });
                    });
                }

                // doTest(isBuffered, isLocked)
                doTest(true, true);
                doTest(true, false);
                doTest(false, false);
                doTest(false, true);
            });
        });
    });

    describe('hideMode', function() {
        // See EXTJS-17978.
        var item;

        beforeEach(function() {
            createCardContainer({
                items: [makeCard('Foo'), {
                    title: "The Owl's Nest Farm"
                }]
            });

            item = comp.items.getAt(0);
        });

        afterEach(function() {
            item = null;
        });

        it('should be automatically set to use offsets', function() {
            expect(item.hideMode).toBe('offsets');
        });

        it('should cache the original hideMode value', function() {
            expect(item.originalHideMode).toBe('display');
        });

        it('should restore the original hideMode value when the item is removed from the layout', function() {
            comp.remove(item);
            expect(item.hideMode).toBe('display');
        });

        it('should remove the cached hideMode value when the item is removed from the layout', function() {
            comp.remove(item);
            expect(item.originalHideMode).toBe(undefined);
        });
    });

    describe('different xtypes as containers', function() {
        // Behavior should be the same regardless of the xtype.
        var item, scrollable, scrollPosition;

        function makeIt(xtype) {
            createCardContainer({
                xtype: xtype,
                items: [makeCard('Foo'), {
                    title: "The Owl's Nest Farm"
                }]
            });

            item = comp.items.getAt(0);
            scrollable = item.scrollable;

            expect(comp.xtype).toBe(xtype);
        }

        afterEach(function() {
            item = scrollable = scrollPosition = null;
        });

        function runTests(xtype) {
            describe('should work for ' + xtype + ' as the container', function() {
                it('should set proper hideMode value', function() {
                    makeIt(xtype);

                    expect(item.hideMode).toBe('offsets');
                });

                it('should preserve scroll position', function() {
                    makeIt(xtype);

                    scrollable.scrollTo(0, 7000);
                    scrollPosition = scrollable.getPosition();

                    // Toggle.
                    comp.setActiveItem(1);
                    comp.setActiveItem(0);

                    expect(scrollable.getPosition().y).toBe(scrollPosition.y);
                });
            });
        }

        runTests('container');
        runTests('panel');
        runTests('tabpanel');
    });
});

