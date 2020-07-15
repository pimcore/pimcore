topSuite("Ext.menu.KeyNav", [false, 'Ext.menu.Menu'], function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        menu;

    function makeMenu(cfg) {
        menu = new Ext.menu.Menu(Ext.apply({
            text: 'Main Menu',
            width: 120,
            height: 120,
            floating: false,
            renderTo: Ext.getBody(),
            items: [{
                text: 'Menu One'
            }, {
                text: 'Menu Two',
                // Show the menu straightaway after mouseover.
                menuExpandDelay: 0,
                menu: {
                    items: [{
                        text: 'Next Level'
                    }, {
                        text: 'Next Level'
                    }, {
                        text: 'Next Level'
                    }]
                }
            }, {
                text: 'Menu Three'
            }, {
                text: 'Menu Four'
            }]
        }, cfg));
    }

    afterEach(function() {
        menu.hide();
        Ext.destroy(menu);
        menu = null;
    });

    xdescribe('enter key nav', function() {
        describe('href property', function() {
            // Note that the specs were failing in FF 24 without the waitsFor().
            // Note that it's necessary to set the activeItem and focusedItem to test the API!
            var menuItem;

            afterEach(function() {
                menuItem = null;
                window.location.hash = '';
            });

            it('should follow the target', function() {
                makeMenu({
                    items: [{
                        text: 'menu item one',
                        href: '#ledzep'
                    }, {
                        text: 'menu item two'
                    }]
                });

                menuItem = menu.items.first();
                menu.activeItem = menu.focusedItem = menuItem;
                jasmine.fireKeyEvent(menuItem.itemEl.dom, 'keydown', 13);

                waitsFor(function() {
                    return location.hash === '#ledzep';
                }, 'hash to change', 1000);

                runs(function() {
                    expect(location.hash).toBe('#ledzep');
                });
            });

            it('should not follow the target if the click listener stops the event', function() {
                var hashValue = '';

                makeMenu({
                    items: [{
                        text: 'menu item one',
                        href: '#motley',
                        listeners: {
                            click: function(cmp, e) {
                                e.preventDefault();
                            }
                        }
                    }, {
                        text: 'menu item two'
                    }]
                });

                menuItem = menu.items.first();
                menu.activeItem = menu.focusedItem = menuItem;
                jasmine.fireKeyEvent(menuItem.itemEl.dom, 'keydown', 13);

                waitsFor(function() {
                    return location.hash === hashValue;
                }, 'timed out waiting for hash to change', 1000);

                runs(function() {
                    expect(location.hash).toBe(hashValue);
                });
            });
        });
    });

    describe('left key nav', function() {
        var node, childMenu;

        beforeEach(function() {
            makeMenu();
        });

        afterEach(function() {
            node = childMenu = null;
        });

        itNotTouch('should only hide child menus', function() {
            // Activate the menu item and expand its menu.
            node = menu.down('[text="Menu Two"]').el.dom;
            jasmine.fireMouseEvent(node, 'mouseover');

            // Do the keypress to test the API.
            childMenu = menu.down('menu');

            waitsFor(function() {
                return childMenu.el;
            });

            runs(function() {
                pressKey(childMenu.down('menuitem'), 'left');
            });

            runs(function() {
                expect(childMenu.hidden).toBe(true);
            });
        });

        describe('parent menu', function() {
            it('should not hide', function() {
                // Test the parent menu.
                node = menu.el.down('.x-menu-item-link', true);
                jasmine.fireKeyEvent(node, 'keydown', 37);

                expect(menu.hidden).toBe(false);
            });

            itNotTouch('should not hide (tests hiding child menu first)', function() {
                // Activate the menu item and expand its menu.
                node = menu.down('[text="Menu Two"]').el.dom;
                jasmine.fireMouseEvent(node, 'mouseover');

                // Hide the child menu.
                childMenu = menu.down('menu');

                waitsFor(function() {
                    return childMenu.el;
                });

                runs(function() {
                    node = childMenu.el.down('.x-menu-item-link', true);
                    jasmine.fireKeyEvent(node, 'keydown', 37);

                    // Test the parent menu.
                    node = menu.el.down('.x-menu-item-link', true);
                    jasmine.fireKeyEvent(node, 'keydown', 37);

                    expect(menu.hidden).toBe(false);
                });
            });
        });
    });
});
