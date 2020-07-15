topSuite("Ext.menu.Menu",
    ['Ext.Panel', 'Ext.Button', 'Ext.form.field.Date',
     'Ext.layout.container.Accordion', 'Ext.layout.container.Fit'],
function() {
    var menu;

    function makeMenu(cfg) {
        menu = new Ext.menu.Menu(cfg || {});

        return menu;
    }

    function doItemMouseover(item) {
        var targetEl = item.ariaEl,
            x = targetEl.getX() + targetEl.getWidth() / 2,
            y = targetEl.getY() + targetEl.getHeight() / 2;

        if (jasmine.supportsTouch) {
            Ext.testHelper.touchStart(targetEl, { x: x, y: y });
            Ext.testHelper.touchEnd(targetEl, { x: x, y: y });
        }
        else {
            jasmine.fireMouseEvent(targetEl, 'mouseover');
        }
    }

    function doItemClick(item) {
        var targetEl = item.ariaEl,
            x = targetEl.getX() + targetEl.getWidth() / 2,
            y = targetEl.getY() + targetEl.getHeight() / 2;

        if (jasmine.supportsTouch) {
            Ext.testHelper.touchStart(targetEl, { x: x, y: y });
            Ext.testHelper.touchEnd(targetEl, { x: x, y: y });
        }
        else {
            jasmine.fireMouseEvent(targetEl, 'click');
        }
    }

    function doElementMousedown(el) {
        el = Ext.get(el);

        var x = el.getX() + el.getWidth() / 2,
            y = el.getY() + el.getHeight() / 2;

        if (jasmine.supportsTouch) {
            Ext.testHelper.touchStart(el, { x: x, y: y });
        }
        else {
            jasmine.fireMouseEvent(el, 'mousedown');
        }
    }

    function doElementMouseup(el) {
        el = Ext.get(el);

        var x = el.getX() + el.getWidth() / 2,
            y = el.getY() + el.getHeight() / 2;

        if (jasmine.supportsTouch) {
            Ext.testHelper.touchEnd(el, { x: x, y: y });
        }
        else {
            jasmine.fireMouseEvent(el, 'mouseup');
        }
    }

    afterEach(function() {
        if (menu && !menu.destroyed) {
            menu.hide();
            Ext.destroy(menu);
        }

        menu = null;
    });

    describe("defaultType", function() {
        it("should be menuitem", function() {
            makeMenu({
                items: [{}, {}]
            });
            expect(menu.items.getAt(0).$className).toBe('Ext.menu.Item');
            expect(menu.items.getAt(1).$className).toBe('Ext.menu.Item');
        });

        it("should create a check item if there is a checked property and no xtype", function() {
            makeMenu({
                items: [{
                    checked: true
                }, {
                    xtype: 'menuitem',
                    checked: false
                }]
            });
            expect(menu.items.getAt(0).$className).toBe('Ext.menu.CheckItem');
            expect(menu.items.getAt(1).$className).toBe('Ext.menu.Item');
        });

        it("should allow a custom default", function() {
            makeMenu({
                defaultType: 'menucheckitem',
                items: [{}, {}, { checked: true }]
            });
            expect(menu.items.getAt(0).$className).toBe('Ext.menu.CheckItem');
            expect(menu.items.getAt(1).$className).toBe('Ext.menu.CheckItem');
            expect(menu.items.getAt(2).$className).toBe('Ext.menu.CheckItem');
        });
    });

    describe('dockedItems', function() {
        it('should move body below docked title', function() {
            makeMenu({
                title: 'Some Menu',

                items: [{
                    text: 'Hello'
                }, {
                    text: 'World'
                }]
            });

            menu.show();

            var bodyXY = menu.body.getXY();

            var title = menu.dockedItems.items[0];

            var titleHeight = title.el.getHeight();

            var titleXY = title.el.getXY();

            expect(bodyXY[1]).toBe(titleXY[1] + titleHeight);
        });
    });

    describe("reference", function() {
        it("should have a reference when used as a config on a button", function() {
            // Ensure the state is clean
            Ext.fixReferences();

            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                referenceHolder: true,
                items: {
                    xtype: 'button',
                    text: 'Foo',
                    menu: {
                        reference: 'menu',
                        items: [{
                            reference: 'item'
                        }]
                    }
                }
            });

            menu = ct.items.getAt(0).getMenu();

            expect(ct.lookupReference('menu')).toBe(menu);
            expect(ct.lookupReference('item')).toBe(menu.items.getAt(0));

            ct.destroy();
        });

        it("should have a reference when used as an instance on a button", function() {
            // Ensure the state is clean
            Ext.fixReferences();

            makeMenu({
                reference: 'menu',
                items: [{
                    reference: 'item'
                }]
            });

            var ct = new Ext.container.Container({
                renderTo: Ext.getBody(),
                referenceHolder: true,
                items: {
                    xtype: 'button',
                    text: 'Foo',
                    menu: menu
                }
            });

            expect(ct.lookupReference('menu')).toBe(menu);
            expect(ct.lookupReference('item')).toBe(menu.items.getAt(0));

            ct.destroy();
        });
    });

    describe("MenuManager", function() {
        describe("hideAll", function() {
            it("should hide a single menu", function() {
                makeMenu({
                    items: {
                        text: 'Foo'
                    }
                });
                menu.show();
                expect(menu.isVisible()).toBe(true);
                Ext.menu.Manager.hideAll();
                expect(menu.isVisible()).toBe(false);
            });

            it("should hide multiple menus", function() {
                var m1 = makeMenu({ items: { text: 'M1' }, allowOtherMenus: true }),
                    m2 = makeMenu({ items: { text: 'M2' }, allowOtherMenus: true }),
                    m3 = makeMenu({ items: { text: 'M3' }, allowOtherMenus: true });

                m1.show();
                m2.show();
                m3.show();

                expect(m1.isVisible()).toBe(true);
                expect(m2.isVisible()).toBe(true);
                expect(m3.isVisible()).toBe(true);

                Ext.menu.Manager.hideAll();

                expect(m1.isVisible()).toBe(false);
                expect(m2.isVisible()).toBe(false);
                expect(m3.isVisible()).toBe(false);

                Ext.destroy(m1, m2, m3);
            });

            it("should hide a menu and submenus", function() {
                makeMenu({
                    items: {
                        text: 'Foo',
                        menu: {
                            items: {
                                text: 'Bar'
                            }
                        }
                    }
                });

                menu.show();
                var item = menu.items.first();

                item.activated = true;
                item.expandMenu(null, 0);

                expect(menu.isVisible()).toBe(true);
                expect(item.getMenu().isVisible()).toBe(true);

                Ext.menu.Manager.hideAll();

                expect(menu.isVisible()).toBe(false);
                expect(item.getMenu().isVisible()).toBe(false);
            });

            it("should only hide menus visible at the time of being called", function() {
                var m1 = makeMenu({ allowOtherMenus: true, items: { text: 'Foo' } }),
                    m2 = makeMenu({ allowOtherMenus: true, items: { text: 'Bar' } }),
                    m3 = makeMenu({ allowOtherMenus: true, items: { text: 'Baz' } });

                m1.show();
                m2.show();

                m1.on('hide', function() {
                    m3.show();
                });

                expect(m1.isVisible()).toBe(true);
                expect(m2.isVisible()).toBe(true);
                expect(m3.isVisible()).toBe(false);

                Ext.menu.Manager.hideAll();

                expect(m1.isVisible()).toBe(false);
                expect(m2.isVisible()).toBe(false);
                expect(m3.isVisible()).toBe(true);

                Ext.destroy(m1, m2, m3);
            });
        });

        describe("handling active menus", function() {
            // https://sencha.jira.com/browse/EXTJS-17844
            it("should not hide submenu when parent menu item is clicked", function() {
                makeMenu({
                    items: [{
                        menuExpandDelay: 0,
                        text: 'Menu Item 1',
                        menu: {
                            items: [{
                                text: 'sub-Menu Item 1'
                            }, {
                                text: 'sub-Menu Item 2'
                            }]
                        }
                    }, {
                        text: 'Menu Item 2',
                        menu: {
                            items: [{
                                text: 'sub-Menu Item 1'
                            }, {
                                text: 'sub-Menu Item 2'
                            }]
                        }
                    }]
                });
                menu.show();

                var item = menu.down('[text="Menu Item 1"]');

                // Expand the sub-menu
                doItemMouseover(item);

                waitsFor(function() {
                    return item.menu.isVisible();
                });

                runs(function() {
                    jasmine.fireMouseEvent(item.ariaEl, 'click');

                    // Manager acts on global mousedown with no delays
                    expect(item.menu.isVisible()).toBe(true);
                });
            });
        });
    });

    describe('Touch events', function() {
        // https://sencha.jira.com/browse/EXTJS-20372
        if (jasmine.supportsTouch) {
            it("should not expand submenu when parent item is touched", function() {
                makeMenu({
                    items: [{
                        xtype: 'datefield'
                    }]
                });

                menu.show();

                var field = menu.down('datefield'),
                    trigger = field.triggerEl.item(0),
                    x = trigger.getX() + trigger.getWidth() / 2,
                    y = trigger.getY() + trigger.getHeight() / 2;

                // Touch start must not focus the field
                Ext.testHelper.touchStart(trigger.dom, { x: x, y: y });
                Ext.testHelper.touchEnd(trigger.dom, { x: x, y: y });

                expect(field.getPicker().isVisible()).toBe(true);
            });
        }
    });

    describe("moving menu", function() {
        describe("moving from button to menu item", function() {
            it("should be able to show the menu", function() {
                makeMenu({
                    items: [{
                        text: 'Foo'
                    }]
                });

                var b = new Ext.button.Button({
                    renderTo: Ext.getBody(),
                    text: 'Foo',
                    menu: menu
                });

                b.showMenu();
                b.hideMenu();

                delete menu.menuClickBuffer;

                var other = new Ext.menu.Menu({
                        items: [{
                            text: 'Child',
                            menuExpandDelay: 0
                        }]
                    }),
                    item;

                item = other.items.getAt(0);
                item.setMenu(menu);

                other.show();

                jasmine.focusAndWait(item);

                runs(function() {
                    item.activated = true;
                    item.expandMenu(null, 0);
                    expect(menu.isVisible()).toBe(true);
                    Ext.destroy(other, b);
                });
            });
        });

        describe("moving from menu item to button", function() {
            it("should be able to show the menu", function() {
                makeMenu({
                    menuClickBuffer: 0,
                    items: [{
                        text: 'Foo'
                    }]
                });

                var b = new Ext.button.Button({
                    renderTo: Ext.getBody(),
                    text: 'Foo'
                });

                var other = new Ext.menu.Menu({
                        items: [{
                            text: 'Child',
                            menuExpandDelay: 0,
                            menu: menu
                        }]
                    }),
                    item;

                item = other.items.getAt(0);

                other.show();

                jasmine.focusAndWait(item);

                runs(function() {
                    item.activated = true;
                    item.expandMenu(null, 0);

                    other.hide();
                    b.setMenu(menu);
                    delete menu.menuClickBuffer;
                    b.showMenu();
                    expect(menu.isVisible()).toBe(true);
                    Ext.destroy(other, b);
                });
            });
        });
    });

    describe('hiding all other menus', function() {
        var menu1, menu2;

        afterEach(function() {
            Ext.destroy(menu1, menu2);
            menu1 = menu2 = null;
        });

        it('should hide all other menus on menu show', function() {
            menu1 = makeMenu();
            menu2 = makeMenu();

            menu1.show();
            expect(menu1.isVisible()).toBe(true);

            // Showing another menu should hide menu1
            menu2.show();
            expect(menu1.isVisible()).toBe(false);
            expect(menu2.isVisible()).toBe(true);
        });

        it('should not hide all other menus on menu show when allowOtherMenus: true', function() {
            menu1 = makeMenu({ allowOtherMenus: true });
            menu2 = makeMenu({ allowOtherMenus: true });

            menu1.show();
            expect(menu1.isVisible()).toBe(true);

            // Showing another menu should NOT hide menu1 because of allowOtherMenus setting
            menu2.show();
            expect(menu2.isVisible()).toBe(true);
            expect(menu2.isVisible()).toBe(true);
        });

        it("should not hide menus when they are a child of a direct menu item", function() {
            makeMenu({
                items: [{
                    text: 'Foo',
                    menu: {
                        items: [{
                            text: 'Bar'
                        }]
                    }
                }]
            });

            var item = menu.items.getAt(0),
                child = item.getMenu();

            menu.show();

            item.activated = true;
            item.expandMenu(null, 0);

            expect(menu.isVisible()).toBe(true);
            expect(child.isVisible()).toBe(true);
        });

        it("should not hide menus when they are nested as part of other components", function() {
            makeMenu({
                items: {
                    xtype: 'container',
                    items: [{
                        xtype: 'button',
                        text: 'Child',
                        menu: {
                            items: [{
                                text: 'Foo'
                            }]
                        }
                    }]
                }
            });

            menu.show();

            var button = menu.items.getAt(0).items.getAt(0),
                child = button.getMenu();

            button.showMenu();

            expect(menu.isVisible()).toBe(true);
            expect(child.isVisible()).toBe(true);
        });
    });

    describe("hiding when doing an action outside the menu", function() {
        it("should hide the menu", function() {
            var field = new Ext.form.field.Text({
                renderTo: Ext.getBody()
            });

            makeMenu({
                items: [{
                    text: 'Foo'
                }]
            });
            menu.show();
            doElementMousedown(field.inputEl);
            expect(menu.isVisible()).toBe(false);
            doElementMouseup(field.inputEl);
            field.destroy();
        });

        it("should hide the menu even if the event propagation is stopped", function() {
            var el = Ext.getBody().createChild({
                tag: 'input'
            });

            el.on('mousedown', function(e) {
                e.stopPropagation();
            });

            makeMenu({
                items: [{
                    text: 'Foo'
                }]
            });
            menu.show();
            doElementMousedown(el);
            expect(menu.isVisible()).toBe(false);
            doElementMouseup(el);
            el.destroy();
        });

        it("should hide all menus", function() {
            var field = new Ext.form.field.Text({
                renderTo: Ext.getBody()
            });

            var m1 = makeMenu({ allowOtherMenus: true, items: [{ text: 'Foo' }] }),
                m2 = makeMenu({ allowOtherMenus: true, items: [{ text: 'Bar' }] });

            m1.showAt(100, 100);
            m2.showAt(100, 150);

            doElementMousedown(field.inputEl);
            expect(m1.isVisible()).toBe(false);
            expect(m2.isVisible()).toBe(false);
            doElementMouseup(field.inputEl);
            Ext.destroy(field, m1, m2);
        });
    });

    describe('binding an ownerRef', function() {
        var ctn;

        beforeEach(function() {
            ctn = new Ext.container.Container({
                renderTo: Ext.getBody()
            });
        });

        afterEach(function() {
            ctn.destroy();
            ctn = null;
        });

        it('should bind an ownerCt reference to the menu if added as an item to a container (but not rendered)', function() {
            makeMenu();
            ctn.add(menu);

            expect(menu.ownerCt).toBe(ctn);
        });

        it('should bind an floatParent reference to the menu when shown/rendered', function() {
            makeMenu();
            ctn.add(menu);
            menu.show();

            expect(menu.floatParent).toBe(ctn);
        });

        it('should not have an ownerRef if not a child item of a container', function() {
            makeMenu();
            menu.show();

            expect(menu.floatParent).toBeUndefined();
            expect(menu.ownerCt).toBeUndefined();
        });
    });

    describe("not floating", function() {
        it("should set constrain false", function() {
            makeMenu({
                floating: false
            });
            expect(menu.constrain).toBe(false);
        });

        it('should not hide onFocusLeave', function() {
            makeMenu({
                renderTo: document.body,
                floating: false,
                items: [{
                    text: 'Menu Item 1'
                }]
            });

            menu.items.items[0].focus();

            waitsForFocus(menu);
            runs(function() {
                document.body.focus();
            });
            jasmine.blurAndWait(menu);
            runs(function() {
                expect(menu.isVisible()).toBe(true);
            });
        });
    });

    describe("popup menu", function() {

        it("should have a full-height vertical separator", function() {

            makeMenu({
                id: 'popup-menu',
                items: [{
                    text: 'Short',
                    id: 'popup-menu-short-item'
                }, {
                    text: 'Shrink wrap to my width, and stretch mysibling!',
                    id: 'popup-menu-long-item',
                    style: 'width:268px'
                }]
            });
            menu.showAt(0, 0);

            expect(menu.body.child('.x-menu-icon-separator').getHeight()).toEqual(menu.body.getHeight());
        });

        xit("should stretch the shortest item to match the longest", function() {

            makeMenu({
                id: 'popup-menu',
                items: [{
                    text: 'Short',
                    id: 'popup-menu-short-item'
                }, {
                    text: 'Shrink wrap to my width, and stretch mysibling!',
                    id: 'popup-menu-long-item',
                    style: 'width:268px'
                }]
            });
            menu.showAt(0, 0);

            expect(menu).toHaveLayout({
                "el": {
                    "xywh": "0 0 274 62"
                },
                "body": {
                    "xywh": "0 0 274 62"
                },
                "iconSepEl": {
                    "xywh": "0 0 2 60"
                },
                "items": {
                    "popup-menu-short-item": {
                        "el": {
                            "xywh": "3 3 268 28"
                        },
                        "arrowEl": {
                            "xywh": "62 22 1 1"
                        },
                        "textEl": {
                            "xywh": "36 12 26 13"
                        },
                        "iconEl": {
                            "xywh": "7 8 16 16"
                        },
                        "itemEl": {
                            "xywh": "4 4 266 26"
                        }
                    },
                    "popup-menu-long-item": {
                        "el": {
                            "xywh": "3 31 268 28"
                        },
                        "arrowEl": {
                            "xywh": "267 50 1 1"
                        },
                        "textEl": {
                            "xywh": "36 40 231 13"
                        },
                        "iconEl": {
                            "xywh": "7 36 16 16"
                        },
                        "itemEl": {
                            "xywh": "4 32 266 26"
                        }
                    }
                }
            });
        });
    });

    describe('registering with an owner', function() {
        describe('constrainTo', function() {
            describe('when owner is a button', function() {
                var button;

                beforeEach(function() {
                    makeMenu({
                        width: 200,
                        items: [{
                            text: 'foo'
                        }]
                    });
                });

                afterEach(function() {
                    button.destroy();
                    button = null;
                });

                it('should not constrain itself to the button', function() {
                    button = new Ext.button.Button({
                        menu: menu,
                        renderTo: Ext.getBody()
                    });

                    button.showMenu();

                    expect(button.menu.constrainTo).toBeUndefined();
                });
            });
        });
    });

    // These and corresponding specs in Ext.menu.Item and Ext.menu.KeyNav test suites
    // disabled because they don't work as expected.
    xdescribe('navigation', function() {
        var hash = '#foo',
            hashChangeHandler;

        beforeEach(function() {
            hashChangeHandler = jasmine.createSpy();
            Ext.getWin().on('hashchange', hashChangeHandler);
            location.hash = hash;

            waitsFor(function() {
                return hashChangeHandler.callCount === 1;
            });
        });

        afterEach(function() {
            var callCount = hashChangeHandler.callCount;

            location.hash = '';

            waitsFor(function() {
                return hashChangeHandler.callCount === (callCount + 1);
            });

            runs(function() {
                Ext.getWin().un('hashchange', hashChangeHandler);
            });
        });

        it("should navigate when a child item has an href config", function() {
            makeMenu({
                renderTo: Ext.getBody(),
                width: 400,
                floating: false,
                items: [{
                    text: 'item with href',
                    href: '#blah'
                }]
            });

            jasmine.fireMouseEvent(menu.items.getAt(0).itemEl.dom, 'click');

            waitsFor(function() {
                return hashChangeHandler.callCount === 2;
            });

            runs(function() {
                expect(location.hash).toBe('#blah');
            });
        });

        it("should not navigate when a child item does not have an href config", function() {
            makeMenu({
                renderTo: Ext.getBody(),
                width: 400,
                floating: false,
                items: [{
                    text: 'item with no href'
                }]
            });

            doItemClick(menu.items.getAt(0));

            // since hashchange happens asynchronously the only way to test that it did not
            // happen is to wait a bit
            waits(100);

            runs(function() {
                expect(hashChangeHandler.callCount).toBe(1);
            });
        });
    });

    describe("ARIA attributes", function() {
        describe("floating", function() {
            beforeEach(function() {
                makeMenu();

                // To render
                menu.show();
                menu.hide();
            });

            describe("tabIndex", function() {
                it("should be present on main el", function() {
                    expect(menu.el).toHaveAttr('tabIndex', '-1');
                });
            });

            describe("aria-expanded", function() {
                it("should be false when hidden", function() {
                    expect(menu).toHaveAttr('aria-expanded', 'false');
                });

                it("should be true after showing", function() {
                    menu.show();

                    expect(menu).toHaveAttr('aria-expanded', 'true');
                });
            });
        });

        describe("non-floating", function() {
            beforeEach(function() {
                makeMenu({
                    floating: false,
                    renderTo: Ext.getBody()
                });
            });

            it("should not have aria-expanded attribute", function() {
                expect(menu).not.toHaveAttr('aria-expanded');
            });

            it("should not have tabIndex on main el", function() {
                expect(menu.el).not.toHaveAttr('tabIndex');
            });
        });
    });

    describe("focus reversion", function() {
        var foo, bar, item, activeMenu;

        function showItem(level, text) {
            var tempMenu, tempItem;

            tempMenu = menu;

            for (var i = 1; i <= level; i++) {
                tempItem = tempMenu.down('[text="submenu ' + i + '"]');

                if (tempItem && tempItem.menu) {
                    tempMenu = tempItem.menu;

                    tempItem.focus();
                    tempItem.expandMenu(null, 0);
                }
            }

            if (tempMenu && text) {
                item = tempMenu.down('[text="' + text + '"]');
            }

            if (item) {
                item.focus();
                activeMenu = item.ownerCt;
            }

            return item;
        }

        beforeEach(function() {
            foo = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'foo',
                menu: makeMenu({
                    itemId: 'topMenu',
                    defaults: {
                        hideOnClick: false
                    },

                    items: [{
                        text: 'item 1'
                    }, {
                        text: 'item 2'
                    }, {
                        text: 'submenu 1',
                        menu: {
                            itemId: 'submenu1',
                            items: [{
                                text: 'item 1'
                            }, {
                                text: 'item 2'
                            }, {
                                text: 'submenu 2',
                                menu: {
                                    itemId: 'submenu2',
                                    items: [{
                                        text: 'item 1'
                                    }, {
                                        text: 'should be enough'
                                    }]
                                }
                            }]
                        }
                    }]
                })
            });

            bar = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'bar'
            });

            pressKey(foo, 'down');

            waitsFor(function() {
                return menu.isVisible() && menu.items.items[0].hasFocus;
            }, 'menu to show and gain focus', 1000);
        });

        afterEach(function() {
            Ext.destroy(foo, bar);
            foo = bar = item = null;
        });

        it("should capture focus anchor when opening", function() {
            runs(function() {
                showItem(2, "should be enough");
            });

            waitsFor(function() {
                return item.hasFocus;
            });

            runs(function() {
                expect(menu.getInherited().topmostFocusEvent.relatedTarget).toBe(foo.el.dom);
                expect(menu.down('#submenu1').getInherited().topmostFocusEvent.relatedTarget).toBe(foo.el.dom);
                expect(menu.down('#submenu2').getInherited().topmostFocusEvent.relatedTarget).toBe(foo.el.dom);
            });
        });

        it("should be able to find its owning focusable", function() {
            runs(function() {
                menu.hide();
            });

            expectFocused(foo);
        });

        it("should tab from 1st level menu to bar", function() {
            runs(function() {
                showItem(0, 'item 1');
            });

            waitsFor(function() {
                return item.hasFocus;
            });

            runs(function() {
                simulateTabKey(item, true);
            });

            expectFocused(bar);
        });

        it("should shift-tab from 1st level menu to foo", function() {
            runs(function() {
                showItem(0, 'item 2');
            });

            waitsFor(function() {
                return item.hasFocus;
            });

            runs(function() {
                simulateTabKey(item, false);
            });

            expectFocused(foo);
        });

        it("should tab from 2nd level menu to bar", function() {
            runs(function() {
                showItem(1, 'item 1');
            });

            waitsFor(function() {
                return item.hasFocus;
            });

            runs(function() {
                simulateTabKey(item, true);
            });

            expectFocused(bar);
        });

        it("should tab from 3rd level menu to bar", function() {
            runs(function() {
                showItem(2, "should be enough");
            });

            waitsFor(function() {
                return item.hasFocus;
            });

            runs(function() {
                simulateTabKey(item, true);
            });

            expectFocused(bar);
        });
    });

    describe("cleanup", function() {
        var Manager = Ext.menu.Manager;

        beforeEach(function() {
            makeMenu();
            menu.show();
        });

        it("should be removed from visible array when hiding", function() {
            menu.hide();

            var doesContain = Ext.Array.contains(Manager.visible, menu);

            expect(doesContain).toBe(false);
        });

        it("should be removed from visible array after destroying", function() {
            menu.destroy();

            var doesContain = Ext.Array.contains(Manager.visible, menu);

            expect(doesContain).toBe(false);
        });
    });

    describe('Multi level menus', function() {
        var testContainer,
            button,
            secondButton,
            topMenu,
            topMenuItem,
            menu2,
            menu2Item,
            menu3,
            menu3Item,
            menu3Item1,
            clicked;

        beforeEach(function() {
            testContainer = new Ext.container.Container({
                renderTo: Ext.getBody(),
                width: 300,
                items: [{
                        xtype: 'button',
                        text: 'A Button'
                    }, button = new Ext.button.Button({
                    xtype: 'button',
                    text: 'Foo',
                    menu: topMenu = new Ext.menu.Menu({
                        onBeforeShow: function() {
                            return true;
                        },
                        items: topMenuItem = new Ext.menu.Item({
                            text: 'Top menu item',
                            menu: menu2 = new Ext.menu.Menu({
                                items: menu2Item = new Ext.menu.Item({
                                    text: 'Second level menu item',
                                    menu: menu3 = new Ext.menu.Menu({
                                        items: [
                                            // First item is disabled.
                                            // http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#menu
                                            // It should still receive focus
                                            menu3Item = new Ext.menu.Item({
                                                text: 'Third level menu',
                                                disabled: true
                                            }), menu3Item1 = new Ext.menu.Item({
                                                text: 'Third level second item',
                                                handler: function() {
                                                    clicked = true;
                                                }
                                            })
                                        ]
                                    })
                                })
                            })
                        })
                    })
                }), secondButton = new Ext.button.Button({
                    text: 'Bar'
                })]
            });

            testContainer.show();
        });

        afterEach(function() {
            testContainer.destroy();
        });

        it('should hide on click, and on reshow of parent, should not show again', function() {
            pressKey(button, 'down');

            waitsFor(function() {
                return topMenu.isVisible() && topMenuItem.hasFocus;
            }, 'topMenuItem to recieve focus');

            runs(function() {
                pressKey(topMenuItem, 'right');
            });

            waitsFor(function() {
                return menu2.isVisible() && menu2Item.hasFocus;
            }, 'menu2Item to recieve focus');

            runs(function() {
                pressKey(menu2Item, 'right');
            });

            // http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#menu
            // "Disabled menu items receive focus but have no action when Enter or Left Arrow/Right Arrow is pressed. It is important that the state of the menu item be clearly communicated to the user."
            waitsFor(function() {
                return menu3.isVisible() && menu3Item.hasFocus && menu3Item.activated && menu3Item.hasCls(menu3Item.activeCls);
            }, 'menu3Item to recieve focus');

            runs(function() {
                // All three menus must be visible
                expect(topMenu.isVisible()).toBe(true);
                expect(menu2.isVisible()).toBe(true);
                expect(menu3.isVisible()).toBe(true);

                // click the last menu item
                doItemClick(menu3Item1);
            });

            // All menus must have hidden and focus must revert to the button
            waitsFor(function() {
                return clicked === true && !topMenu.isVisible() && !menu2.isVisible() && !menu3.isVisible() && button.hasFocus;
            }, 'all menus to hide');

            runs(function() {
                doItemClick(button);
            });

            waitsFor(function() {
                return topMenu.isVisible();
            }, 'topMenu to show for the second time');

            runs(function() {
                expect(menu2.isVisible()).toBe(false);
                expect(menu3.isVisible()).toBe(false);
            });
        });

        it('should revert focus to owning static component upon TAB out of a descendant.', function() {
            pressKey(button, 'down');

            waitsFor(function() {
                return topMenu.isVisible() && topMenuItem.hasFocus;
            }, 'topMenuItem to recieve focus');

            runs(function() {
                pressKey(topMenuItem, 'right');
            });

            waitsFor(function() {
                return menu2.isVisible() && menu2Item.hasFocus;
            }, 'menu2Item to recieve focus');

            runs(function() {
                pressKey(menu2Item, 'right');
            });

            // http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#menu
            // "Disabled menu items receive focus but have no action when Enter or Left Arrow/Right Arrow is pressed. It is important that the state of the menu item be clearly communicated to the user."
            waitsFor(function() {
                return menu3.isVisible() && menu3Item.hasFocus && menu3Item.activated && menu3Item.hasCls(menu3Item.activeCls);
            }, 'menu3Item to recieve focus');

            runs(function() {
                // All three menus must be visible
                expect(topMenu.isVisible()).toBe(true);
                expect(menu2.isVisible()).toBe(true);
                expect(menu3.isVisible()).toBe(true);

                // TAB off the last menu item. Do not use jasmine.pressTabKey() here
                // because we want synchronous processing
                simulateTabKey();
            });

            // All menus must have hidden and focus must revert to the button, but allowing the TAB default action to then
            // focus the second button
            waitsFor(function() {
                return !topMenu.isVisible() && !menu2.isVisible() && !menu3.isVisible() && secondButton.hasFocus;
            }, 'all menus to hide and owning button to be focused');
        });

        it('should revert focus to relative owning static component upon TAB out of a descendant if owning component was hidden.', function() {
            pressKey(button, 'down');

            waitsFor(function() {
                return topMenu.isVisible() && topMenuItem.hasFocus;
            }, 'topMenuItem to recieve focus');

            runs(function() {
                pressKey(topMenuItem, 'right');
            });

            waitsFor(function() {
                return menu2.isVisible() && menu2Item.hasFocus;
            }, 'menu2Item to recieve focus');

            runs(function() {
                pressKey(menu2Item, 'right');
            });

            // http://www.w3.org/TR/2013/WD-wai-aria-practices-20130307/#menu
            // "Disabled menu items receive focus but have no action when Enter or Left Arrow/Right Arrow is pressed. It is important that the state of the menu item be clearly communicated to the user."
            waitsFor(function() {
                return menu3.isVisible() && menu3Item.hasFocus && menu3Item.activated && menu3Item.hasCls(menu3Item.activeCls);
            }, 'menu3Item to recieve focus');

            runs(function() {
                // All three menus must be visible
                expect(topMenu.isVisible()).toBe(true);
                expect(menu2.isVisible()).toBe(true);
                expect(menu3.isVisible()).toBe(true);

                // This is the crucial step.
                // This is the default focus target on reversion.
                // It should go to the following sibling.
                button.hide();

                // TAB off the last menu item. Do not use jasmine.pressTabKey() here
                // because we want synchronous processing
                simulateTabKey();
            });

            // All menus must have hidden and focus must revert to the second button
            // because the owning button has been hidden.
            // The way it will work is  that focus reversion will focus the 'A Button'
            // button because it will avoid the hidden one. Then natural TAB will occur.
            waitsFor(function() {
                return !topMenu.isVisible() && !menu2.isVisible() && !menu3.isVisible() && secondButton.hasFocus;
            }, 'all menus to hide and second button to be focused');
        });
    });

    describe("keyboard interaction", function() {
        var item, submenu, subitem1, subitem2;

        beforeEach(function() {
            makeMenu({
                items: [{
                    text: 'item'
                }, {
                    text: 'submenu',
                    menu: [{
                        text: 'subitem 1'
                    }, {
                        text: 'subitem 2'
                    }]
                }]
            });

            item = menu.down('[text=item]');
            submenu = menu.down('[text=submenu]');
            subitem1 = submenu.menu.down('[text="subitem 1"]');
            subitem2 = submenu.menu.down('[text="subitem 2"]');
        });

        afterEach(function() {
            item = submenu = subitem1 = subitem2 = null;
        });

        describe("opening", function() {
            var submenuSpy;

            beforeEach(function() {
                submenuSpy = jasmine.createSpy('submenu show');

                submenu.menu.on('show', submenuSpy);

                menu.show();
            });

            afterEach(function() {
                submenuSpy = null;
            });

            it("should focus the first subitem", function() {
                pressKey(submenu, 'right');

                runs(function() {
                    expectFocused(subitem1, true);
                });
            });

            it("should focus the first subitem again", function() {
                pressKey(submenu, 'right');

                waitForSpy(submenuSpy, 5000);

                runs(function() {
                    expectFocused(subitem1, true);
                    pressKey(subitem1, 'down');
                    pressKey(subitem2, 'esc');
                });

                pressKey(submenu, 'right');

                runs(function() {
                    expectFocused(subitem1);
                });
            });
        });

        // Unfortunately we cannot test that the actual problem is solved,
        // which is scrolling the parent container caused by default action
        // on arrow keys. This is because synthetic injected events do not cause
        // default action. The best we can do is to check that event handlers
        // are calling preventDefault() on the events.
        // See https://sencha.jira.com/browse/EXTJS-18186
        describe("preventing parent scroll", function() {
            var upSpy, downSpy, rightSpy, leftSpy;

            beforeEach(function() {
                upSpy = spyOn(menu, 'onFocusableContainerUpKey').andCallThrough();
                downSpy = spyOn(menu, 'onFocusableContainerDownKey').andCallThrough();
                rightSpy = spyOn(menu, 'onFocusableContainerRightKey').andCallThrough();

                menu.showAt(0, 0);
            });

            afterEach(function() {
                upSpy = downSpy = rightSpy = leftSpy = null;
            });

            it("should preventDefault on the Up arrow key", function() {
                pressKey(submenu, 'up');

                waitForFocus(item);

                runs(function() {
                    expect(upSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                });
            });

            it("should preventDefault on the Down arrow key", function() {
                pressKey(item, 'down');

                waitForFocus(submenu);

                runs(function() {
                    expect(downSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                });
            });

            it("should preventDefault on the Right key", function() {
                pressKey(submenu, 'right');

                runs(function() {
                    waitForFocus(subitem1);
                });

                runs(function() {
                    expect(rightSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                });
            });

            it("should preventDefault on the Left key", function() {
                runs(function() {
                    leftSpy = spyOn(submenu.menu, 'onFocusableContainerLeftKey').andCallThrough();

                    submenu.activated = true;
                    submenu.expandMenu(null, 0);

                    pressKey(subitem1, 'left');
                });

                waitForFocus(submenu);

                runs(function() {
                    expect(leftSpy.mostRecentCall.args[0].defaultPrevented).toBe(true);
                });
            });
        });
    });

    describe("shortcut keys", function() {
        it("should not throw exception when there are no menu items", function() {
            makeMenu();
            menu.show();

            // There's something really weird going on with catching exceptions
            // in event handlers, so call the relevant method directly
            expect(function() {
                var event = new Ext.event.Event({
                    type: 'keydown',
                    charCode: 65, // char 'A'
                    target: menu.el.dom
                });

                menu.onShortcutKey(65, event);
            }).not.toThrow();
        });
    });

    describe('document scrolling', function() {
        it('should not hide when the document scrolls', function() {
            var stretcher = Ext.getBody().createChild({
                style: 'position:absolute;height:1px;width:1px;top:10000px'
            });

            makeMenu();
            menu.show();
            Ext.scroll.Scroller.getScrollingElement().scrollTop = 10000;

            // We must wait for a possibly asynchronous scroll event to happen.
            waits(100);

            runs(function() {
                expect(menu.isVisible()).toBe(true);
                stretcher.destroy();
            });
        });
    });

    // https://sencha.jira.com/browse/EXTJS-20962
    describe("adding separator by shortcut to menu that has defaults", function() {
        beforeEach(function() {
            makeMenu({
                defaults: {
                    iconCls: 'x-fa fa-truck'
                },
                items: [{
                    text: 'Item 1'
                }, '-', {
                    text: 'Item 2'
                }]
            });
        });

        it("should not apply defaults to separator", function() {
            expect(menu.items.getAt(0).iconCls).toBe('x-fa fa-truck');
            expect(menu.items.getAt(1).iconCls).toBeUndefined();
            expect(menu.items.getAt(2).iconCls).toBe('x-fa fa-truck');
        });

        it("should successfully add an instance of Ext.menu.Separator", function() {
            expect(menu.items.getAt(1).getXType()).toBe('menuseparator');
        });
    });

    describe("static, inside an accordion layout", function() {
        var oldOnError = window.onerror;

        afterEach(function() {
            window.onerror = oldOnError;
        });
        it('should not throw an error on mousedown of the header', function() {
            var header,
                onErrorSpy = jasmine.createSpy();

            function getSampleMenuItems() {
                return [
                    { text: 'Menu Item 1' },
                    { text: 'Menu Item 2' },
                    { text: 'Menu Item 3' },
                    { text: 'Menu Item 4' }
                ];
            }

            menu = Ext.widget('panel', {
                title: 'Accordion Panel',
                width: 300,
                height: 500,
                renderTo: Ext.getBody(),
                layout: 'accordion',
                items: [{
                    xtype: 'menu',
                    floating: false,
                    title: 'Menu 1 Title (Throws Exception)',
                        items: getSampleMenuItems()
                }, {
                    xtype: 'menu',
                    floating: false,
                    title: 'Menu 2 Title (Throws Exception)',
                        items: getSampleMenuItems()
                }, {
                    xtype: 'panel',
                    title: 'Panel w/ Fit Menu (Works)',
                    layout: 'fit',
                    items: [{
                        xtype: 'menu',
                        floating: false,
                        items: getSampleMenuItems()
                    }]
                }]
            });

            window.onerror = onErrorSpy.andCallFake(function() {
                if (oldOnError) {
                    oldOnError();
                }
            });

            header = menu.down('menu').header;
            header.titleCmp.focus();
            jasmine.fireMouseEvent(header.el, 'mousedown');

            // Must not have thrown an error
            expect(onErrorSpy).not.toHaveBeenCalled();

            jasmine.fireMouseEvent(header.el, 'mouseup');
        });
    });
});
