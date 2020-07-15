topSuite("Ext.menu.Item", ['Ext.app.ViewModel', 'Ext.app.ViewController'], function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        menu, item;

    function makeMenu(itemCfg, menuCfg) {
        menu = Ext.widget(Ext.apply({
            xtype: 'menu',
            items: itemCfg
        }, menuCfg));
        menu.showAt(0, 0);

        item = menu.items.getAt(0);
    }

    afterEach(function() {
        Ext.destroy(menu, item);
        menu = item = null;
    });

    function clickItem(theItem, doClick) {
        theItem = theItem || item;
        jasmine.fireMouseEvent(theItem.itemEl.dom, 'click');

        // Simulated events does not cause default action on anchors with href
        if (doClick) {
            theItem.itemEl.dom.click();
        }
    }

    describe('on click', function() {
        describe("click event/handler", function() {
            var spy;

            beforeEach(function() {
                spy = jasmine.createSpy();
            });

            afterEach(function() {
                spy = null;
            });

            describe("handler", function() {
                it("should fire the handler", function() {
                    makeMenu({
                        text: 'Foo',
                        handler: spy
                    });
                    clickItem();
                    expect(spy.callCount).toBe(1);
                });

                it("should not fire when disabled", function() {
                    makeMenu({
                        text: 'Foo',
                        handler: spy,
                        disabled: true
                    });
                    clickItem();
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should pass the item and the event object", function() {
                    makeMenu({
                        text: 'Foo',
                        handler: spy
                    });
                    clickItem();

                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(item);
                    expect(args[1] instanceof Ext.event.Event).toBe(true);
                });

                it("should default the scope to the item", function() {
                    makeMenu({
                        text: 'Foo',
                        handler: spy
                    });
                    clickItem();
                    expect(spy.mostRecentCall.object).toBe(item);
                });

                it("should used the passed scope", function() {
                    var o = {};

                    makeMenu({
                        text: 'Foo',
                        scope: o,
                        handler: spy
                    });
                    clickItem();
                    expect(spy.mostRecentCall.object).toBe(o);
                });

                it("should be able to route the handler to a view controller", function() {
                    var ctrl = new Ext.app.ViewController();

                    ctrl.onFoo = spy;
                    makeMenu({
                        text: 'Foo',
                        handler: 'onFoo'
                    }, {
                        controller: ctrl
                    });
                    clickItem();
                    expect(spy.callCount).toBe(1);
                });

                it("should have the menu hidden when the handler fires with hideOnClick: true", function() {
                    var visible;

                    makeMenu({
                        text: 'Foo',
                        handler: spy.andCallFake(function() {
                            visible = menu.isVisible();
                        })
                    });
                    clickItem();
                    expect(visible).toBe(false);
                });

                it("should fire the handler after the click event", function() {
                    var order = [];

                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: function() {
                                order.push('click');
                            }
                        },
                        handler: function() {
                            order.push('handler');
                        }
                    });
                    clickItem();
                    expect(order).toEqual(['click', 'handler']);
                });

                it("should not call the handler if the click event returns false", function() {
                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: function() {
                                return false;
                            }
                        },
                        handler: spy
                    });
                    clickItem();
                    expect(spy).not.toHaveBeenCalled();
                });

                it("it should not fire the callback if the menu is destroyed in the click event", function() {
                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: function() {
                                menu.destroy();
                            }
                        },
                        handler: spy
                    });

                    // Error messages are expected
                    spyOn(Ext, 'log');
                    spyOn(Ext.Error, 'raise');
                    clickItem();

                    expect(spy).not.toHaveBeenCalled();
                });
            });

            describe("click event", function() {
                it("should fire the click event", function() {
                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: spy
                        }
                    });
                    clickItem();
                    expect(spy.callCount).toBe(1);
                });

                it("should not fire when disabled", function() {
                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: spy
                        },
                        disabled: true
                    });
                    clickItem();
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should pass the item and the event object", function() {
                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: spy
                        }
                    });
                    clickItem();

                    var args = spy.mostRecentCall.args;

                    expect(args[0]).toBe(item);
                    expect(args[1] instanceof Ext.event.Event).toBe(true);
                });

                it("should have the menu hidden when the handler fires with hideOnClick: true", function() {
                    var visible;

                    makeMenu({
                        text: 'Foo',
                        listeners: {
                            click: spy.andCallFake(function() {
                                visible = menu.isVisible();
                            })
                        }
                    });
                    clickItem();
                    expect(visible).toBe(false);
                });
            });
        });

        describe("hideOnClick", function() {
            it("should hide the menu with hideOnClick: true", function() {
                makeMenu({
                    text: 'Foo',
                    hideOnClick: true
                });
                clickItem();
                expect(menu.isVisible()).toBe(false);
            });

            it("should not hide the menu with hideOnClick: false", function() {
                makeMenu({
                    text: 'Foo',
                    hideOnClick: false
                });
                clickItem();
                expect(menu.isVisible()).toBe(true);
            });

            describe("hierarchy", function() {
                function expand(item) {
                    item.activated = true;
                    item.expandMenu(null, 0);

                    return item.menu;
                }

                it("should hide a parent menu", function() {
                    makeMenu({
                        text: 'Foo',
                        menu: {
                            items: [{
                                text: 'Bar'
                            }]
                        }
                    });

                    var item = menu.items.first(),
                        subMenu = item.menu;

                    expand(item);
                    clickItem(subMenu.items.first());
                    expect(subMenu.isVisible()).toBe(false);
                    expect(menu.isVisible()).toBe(false);
                });

                it("should hide all parent menus", function() {
                    makeMenu({
                        text: 'Foo',
                        menu: {
                            items: {
                                text: 'Bar',
                                menu: {
                                    items: {
                                        text: 'Baz',
                                        menu: {
                                            items: {
                                                text: 'Qux'
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    });

                    var sub1 = expand(menu.items.first()),
                        sub2 = expand(sub1.items.first()),
                        sub3 = expand(sub2.items.first());

                    clickItem(sub3.items.first());

                    expect(sub3.isVisible()).toBe(false);
                    expect(sub2.isVisible()).toBe(false);
                    expect(sub1.isVisible()).toBe(false);
                    expect(menu.isVisible()).toBe(false);
                });
            });
        });

        describe('href property', function() {
            // Note that the specs were failing in FF 24 without the waitsFor().
            // Note that it's necessary to set the activeItem and focusedItem to test the API!
            var menuItem;

            afterEach(function() {
                menuItem = null;
                location.hash = '';
            });

            xit('should follow the target', function() {
                makeMenu([{
                    text: 'menu item one',
                    href: '#ledzep'
                }, {
                    text: 'menu item two'
                }]);
                item.focus();

                waitsForFocus(item);

                runs(function() {
                    clickItem(item, true);
                });

                waitsFor(function() {
                    return location.hash === '#ledzep';
                }, 'timed out waiting for hash to change', 1000);

                runs(function() {
                    expect(location.hash).toBe('#ledzep');
                });
            });

            // TODO This test does not work properly due to events being translated
            // TODO: Reinstate this for touch platforms when https://sencha.jira.com/browse/EXT-4 is fixed.
            // We cannot now preventDefault on native click events on touch because of click event synthesis.
            xit('should not follow the target link if the click listener stops the event', function() {
                var hashValue = Ext.isIE ? '#' : '';

                makeMenu([{
                    text: 'menu item one',
                    href: '#motley',
                    listeners: {
                        click: function(cmp, e) {
                            e.preventDefault();
                        }
                    }
                }, {
                    text: 'menu item two'
                }]);

                menu.activeItem = menu.focusedItem = item;
                clickItem(item, Ext.isIE9m);

                waitsFor(function() {
                    return location.hash === hashValue;
                }, 'timed out waiting for hash to change', 1000);

                runs(function() {
                    expect(location.hash).toBe(hashValue);
                });
            });
        });
    });

    describe('disabled', function() {
        describe('when item has an href config', function() {
            it('should stop the event', function() {
                makeMenu({
                    disabled: true,
                    href: '#menu'
                });
                clickItem();
                expect(location.hash).not.toBe('menu');
            });
        });

        itNotTouch("should gain focus and activate on mouseover", function() {
            makeMenu([{
                text: 'Foo',
                disabled: true
            }]);
            var item = menu.items.getAt(0);

            jasmine.fireMouseEvent(item.getEl(), 'mouseover');
            waitsFor(function() {
                return item.containsFocus === true;
            }, "Never focused");
            runs(function() {
                expect(item.activated).toBe(true);
            });
        });

        describe("submenu", function() {
            itNotTouch("should not show a submenu on mouseover", function() {
                makeMenu([{
                    text: 'Foo',
                    disabled: true,
                    menuExpandDelay: 0,
                    menu: {
                        items: [{
                            text: 'Sub1'
                        }]
                    }
                }]);

                var item = menu.items.getAt(0),
                    sub = item.getMenu();

                jasmine.fireMouseEvent(item.getEl(), 'mouseover');
                waitsFor(function() {
                    return item.containsFocus === true;
                }, "Never focused");
                runs(function() {
                    expect(sub.isVisible()).toBe(false);
                });
            });
        });
    });

    describe('when destroying', function() {
        var m;

        beforeEach(function() {
            m = new Ext.menu.Menu();

            makeMenu([{
                text: 'The Office, UK',
                menu: m
            }]);

            item.destroy();

        });

        afterEach(function() {
            m = null;
        });

        it('should destroy its menu', function() {
            expect(m.destroyed).toBe(true);
        });

        it('should cleanup its menu reference', function() {
            expect(item.menu).toBe(null);
        });
    });

    describe("binding", function() {
        it("should be able to bind properties higher up in the hierarchy", function() {
            var vm = new Ext.app.ViewModel({
                data: {
                    title: 'someTitle'
                }
            });

            makeMenu({
                text: 'Foo',
                menu: {
                    bind: {
                        title: '{title}'
                    }
                }
            }, {
                viewModel: vm
            });
            var subMenu = item.menu;

            // Render to force the VM to fire
            subMenu.show();
            vm.notify();
            expect(subMenu.getTitle()).toBe('someTitle');
        });
    });

    describe("ARIA", function() {
        describe("simple", function() {
            beforeEach(function() {
                makeMenu({
                    text: 'foo'
                });

                menu.show();
            });

            it("should have itemEl as ariaEl", function() {
                expect(item.ariaEl).toBe(item.itemEl);
            });

            it("should have menuitem role", function() {
                expect(item).toHaveAttr('role', 'menuitem');
            });

            it("should not have aria-haspopup", function() {
                expect(item).not.toHaveAttr('aria-haspopup');
            });

            it("should not have aria-owns", function() {
                expect(item).not.toHaveAttr('aria-owns');
            });
        });

        describe("plain", function() {
            beforeEach(function() {
                makeMenu({
                    text: 'plain',
                    plain: true
                });

                menu.show();
            });

            it("should have el as ariaEl", function() {
                expect(item.ariaEl).toBe(item.el);
            });

            it("should have menuitem role", function() {
                expect(item).toHaveAttr('role', 'menuitem');
            });

            it("should not have aria-haspopup", function() {
                expect(item).not.toHaveAttr('aria-haspopup');
            });

            it("should not have aria-owns", function() {
                expect(item).not.toHaveAttr('aria-owns');
            });
        });

        describe("with submenu", function() {
            describe("via config", function() {
                beforeEach(function() {
                    makeMenu({
                        text: 'submenu',
                        menu: {
                            items: [{
                                text: 'sub-item'
                            }]
                        }
                    });

                    menu.show();
                });

                it("should have aria-haspopup", function() {
                    expect(item).toHaveAttr('aria-haspopup', 'true');
                });

                it("should have aria-owns", function() {
                    expect(item).toHaveAttr('aria-owns', item.menu.id);
                });
            });

            describe("adding via setMenu", function() {
                beforeEach(function() {
                    makeMenu({
                        text: 'submenu'
                    });
                });

                describe("before rendering", function() {
                    beforeEach(function() {
                        item.setMenu({
                            items: [{
                                text: 'sub-item'
                            }]
                        });

                        menu.show();
                    });

                    it("should have aria-haspopup", function() {
                        expect(item).toHaveAttr('aria-haspopup', 'true');
                    });

                    it("should have aria-owns", function() {
                        expect(item).toHaveAttr('aria-owns', item.menu.id);
                    });
                });

                describe("after rendering", function() {
                    beforeEach(function() {
                        menu.show();

                        item.setMenu({
                            items: [{
                                text: 'sub-item'
                            }]
                        });
                    });

                    it("should have aria-haspopup", function() {
                        expect(item).toHaveAttr('aria-haspopup', 'true');
                    });

                    it("should have aria-owns", function() {
                        expect(item).toHaveAttr('aria-owns', item.menu.id);
                    });
                });
            });

            describe("removing via setMenu", function() {
                beforeEach(function() {
                    makeMenu({
                        text: 'submenu',
                        menu: {
                            items: [{
                                text: 'sub-item'
                            }]
                        }
                    });
                });

                describe("before rendering", function() {
                    beforeEach(function() {
                        item.setMenu(null);
                    });

                    it("should not have aria-haspopup", function() {
                        expect(item).not.toHaveAttr('aria-haspopup');
                    });

                    it("should have no aria-owns", function() {
                        expect(item).not.toHaveAttr('aria-owns');
                    });
                });

                describe("after rendering", function() {
                    beforeEach(function() {
                        menu.show();
                        item.setMenu(null);
                    });

                    it("should not have aria-haspopup", function() {
                        expect(item).not.toHaveAttr('aria-haspopup');
                    });

                    it("should not have aria-owns", function() {
                        expect(item).not.toHaveAttr('aria-owns');
                    });
                });
            });
        });
    });

    describe('icon', function() {
        it('should switch from using icon to glyph', function() {
            makeMenu({
                text: 'Foo',
                icon: 'resources/images/foo.gif'
            });

            // Must start with icon
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(item.iconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);

            // Hex 48 is "H". Must switch to using that with no background image
            item.setGlyph('x48@FontAwesome');
            expect(item.iconEl.getStyle('background-image')).toBe('none');
            expect(item.iconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(item.iconEl.dom.innerHTML).toBe('H');
        });
        it('should switch from using icon to iconCls', function() {
            makeMenu({
                text: 'Foo',
                icon: 'resources/images/foo.gif'
            });

            // Must start with icon
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(item.iconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);

            item.setIconCls('foo-icon-class');

            // No glyph character
            expect(item.iconEl.dom.innerHTML).toBe('');

            // iconEl must use the iconCls
            expect(item.iconEl.hasCls('foo-icon-class')).toBe(true);

            expect(item.iconEl.getStyle('background-image')).toBe('none');
        });
    });

    describe('iconCls', function() {
        it('should switch from using iconCls to glyph', function() {
            makeMenu({
                text: 'Foo',
                iconCls: 'foo-icon-class'
            });

            // Must start with iconCls
            expect(item.iconEl.hasCls('foo-icon-class')).toBe(true);

            // Hex 48 is "H". Must switch to using that with no background image
            item.setGlyph('x48@FontAwesome');
            expect(Ext.String.endsWith(item.iconEl.hasCls('foo-icon-class'))).toBe(false);
            expect(item.iconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(item.iconEl.dom.innerHTML).toBe('H');
        });
        it('should switch from using iconCls to icon', function() {
            makeMenu({
                text: 'Foo',
                iconCls: 'foo-icon-class'
            });

            // Must start with iconCls
            expect(item.iconEl.hasCls('foo-icon-class')).toBe(true);

            item.setIcon('resources/images/foo.gif');

            expect(Ext.String.endsWith(item.iconEl.hasCls('foo-icon-class'))).toBe(false);

            // iconEl must use the image as the background image
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(item.iconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);
        });
    });

    describe('glyph', function() {
        it('should switch from using glyph to icon', function() {
            makeMenu({
                text: 'Foo',
                glyph: 'x48@FontAwesome'
            });

            // Hex 48 is "H". Must switch to using that with no background image
            expect(item.iconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(item.iconEl.dom.innerHTML).toBe('H');

            item.setIcon('resources/images/foo.gif');

            // No glyph character
            expect(item.iconEl.dom.innerHTML).toBe('');

            // iconEl must use the image as the background image
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(item.iconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);
        });
        it('should switch from using glyph to iconCls', function() {
            makeMenu({
                text: 'Foo',
                glyph: 'x48@FontAwesome'
            });

            // Hex 48 is "H". Must switch to using that with no background image
            expect(item.iconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(item.iconEl.dom.innerHTML).toBe('H');

            item.setIconCls('foo-icon-class');

            // No glyph character
            expect(item.iconEl.dom.innerHTML).toBe('');

            // iconEl must use the iconCls
            expect(item.iconEl.hasCls('foo-icon-class')).toBe(true);
        });
    });
});
