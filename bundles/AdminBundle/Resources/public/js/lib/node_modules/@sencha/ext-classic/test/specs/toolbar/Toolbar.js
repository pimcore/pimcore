topSuite("Ext.toolbar.Toolbar",
    ['Ext.button.Split', 'Ext.button.Segmented', 'Ext.form.field.Text', 'Ext.form.field.Radio',
     'Ext.slider.Single', 'Ext.layout.container.boxOverflow.Menu'],
function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        toolbar;

    function createToolbar(cfg) {
        toolbar = new Ext.toolbar.Toolbar(Ext.apply({
            width: 200,
            renderTo: Ext.getBody()
        }, cfg || {}));
    }

    afterEach(function() {
        Ext.destroy(toolbar);
        toolbar = null;
    });

    describe("alternate class name", function() {
        it("should have Ext.Toolbar as the alternate class name", function() {
            expect(Ext.toolbar.Toolbar.prototype.alternateClassName).toEqual("Ext.Toolbar");
        });

        it("should allow the use of Ext.Toolbar", function() {
            expect(Ext.Toolbar).toBeDefined();
        });
    });

    it("should default to using a hbox layout", function() {
        createToolbar();
        expect(toolbar.getLayout() instanceof Ext.layout.container.HBox);
    });

    it("should be able to change layout to vertical", function() {
        createToolbar({
            layout: {
                type: 'box',
                vertical: false
            }
        });

        expect(function() {
            toolbar.setLayout({
                type: 'box',
                vertical: true
            });
        }).not.toThrow();
    });

    describe('overflow', function() {
        describe('when enableOverflow is false', function() {
            it('should not create a menu', function() {
                // false is the default value.
                createToolbar({
                    enableOverflow: false
                });
                expect(toolbar.layout.overflowHandler).toBeNull();
            });
        });

        describe('when enableOverflow is true', function() {
            it('should create an overflow menu', function() {
                createToolbar({
                    enableOverflow: true
                });
                expect(toolbar.layout.overflowHandler.menu).toBeDefined();
            });

            it('should create an overflow menu with type "menu"', function() {
                createToolbar({
                    enableOverflow: true
                });
                expect(toolbar.layout.overflowHandler.type).toBe('menu');
            });
        });

        describe('overflow item values', function() {
            it('should sync the values between master and clone fields', function() {
                var menu, barfield, menufield;

                createToolbar({
                    enableOverflow: true,
                    width: 100,
                    items: [{
                        text: 'Foo'
                    }, {
                        text: 'Bar'
                    }, {
                        text: 'Test'
                    }, {
                        xtype: 'textfield'
                    }]
                });
                menu = toolbar.layout.overflowHandler.menu;
                menu.show();

                menufield = menu.down('textfield');
                barfield = menufield.masterComponent;

                menufield.setValue('Foo');

                expect(menufield.getValue()).toBe(barfield.getValue());
            });

            it('should sync the radio field value master and clone when master has been checked', function() {
                var menu, barfield, menufield;

                createToolbar({
                    enableOverflow: true,
                    width: 100,
                    items: [{
                        text: 'Foo'
                    }, {
                        text: 'Bar'
                    }, {
                        text: 'Test'
                    }, {
                        xtype: 'radio',
                        name: 'foo'
                    }]
                });
                menu = toolbar.layout.overflowHandler.menu;
                menu.show();

                barfield = toolbar.down('radio');
                menufield = barfield.overflowClone;

                barfield.setValue(true);

                expect(menufield.getValue()).toBe(barfield.getValue());
            });

            it('should sync the radio field value master and clone when clone has been clicked', function() {
                var menu, barfield, menufield;

                createToolbar({
                    enableOverflow: true,
                    width: 100,
                    items: [{
                        text: 'Foo'
                    }, {
                        text: 'Bar'
                    }, {
                        text: 'Test'
                    }, {
                        xtype: 'radio',
                        name: 'foo'
                    }]
                });
                menu = toolbar.layout.overflowHandler.menu;
                menu.show();

                barfield = toolbar.down('radio');
                menufield = barfield.overflowClone;

                jasmine.fireMouseEvent(menu.el, 'click');
                jasmine.fireMouseEvent(menufield.el, 'click');

                expect(menufield.getValue()).toBe(true);

                expect(menufield.getValue()).toBe(barfield.getValue());
            });

            it('should be able to check and uncheck Checkboxes', function() {
                var menu, barfield, menufield;

                createToolbar({
                    enableOverflow: true,
                    width: 100,
                    defaults: {
                        xtype: 'checkbox'
                    },
                    items: [{
                        boxLabel: 'Foo'
                    }, {
                        boxLabel: 'Bar'
                    }, {
                        boxLabel: 'Test'
                    }, {
                        boxLabel: 'Sencha'
                    }]
                });
                menu = toolbar.layout.overflowHandler.menu;
                menu.show();

                barfield = toolbar.down('checkbox[boxLabel=Sencha]');
                menufield = barfield.overflowClone;

                jasmine.fireMouseEvent(menufield.el, 'click');

                expect(menufield.getValue()).toBe(true);

                jasmine.fireMouseEvent(menufield.el, 'click');

                expect(menufield.getValue()).toBe(false);
            });
        });
    });

    describe('defaultButtonUI', function() {
        it("should use the defaultButtonUI for child buttons with no ui configured on the instance", function() {
            // This test causes layout failure in IE8, but otherwise tests out fine.
            // Since it's not about layout, silencing the error is OK.
            spyOn(Ext.log, 'error');

            createToolbar({
                height: 30,
                defaultButtonUI: 'foo',
                items: [{
                    text: 'Bar'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('foo-small');
        });

        it("should not use the defaultButtonUI for child buttons with ui configured on the instance", function() {
            // See above
            spyOn(Ext.log, 'error');

            createToolbar({
                height: 30,
                defaultButtonUI: 'foo',
                items: [{
                    text: 'Bar',
                    ui: 'bar'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('bar-small');
        });

        it("should not use the defaultButtonUI for child buttons with ui of 'default' configured on the instance", function() {
            createToolbar({
                defaultButtonUI: 'foo',
                items: [{
                    text: 'Bar',
                    ui: 'default'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('default-small');
        });

        it("should use the defaultButtonUI for segmented buttons with no defaultUI configured on the instance", function() {
            createToolbar({
                defaultButtonUI: 'foo',
                items: [{
                    xtype: 'segmentedbutton',
                    items: [{
                        text: 'Bar'
                    }]
                }]
            });

            expect(toolbar.items.getAt(0).getDefaultUI()).toBe('foo');
            expect(toolbar.items.getAt(0).items.getAt(0).ui).toBe('foo-small');
        });

        it("should not use the defaultButtonUI for segmented buttons with defaultUI configured on the instance", function() {
            createToolbar({
                defaultButtonUI: 'foo',
                items: [{
                    xtype: 'segmentedbutton',
                    defaultUI: 'bar',
                    items: [{
                        text: 'Bar'
                    }]
                }]
            });

            expect(toolbar.items.getAt(0).getDefaultUI()).toBe('bar');
            expect(toolbar.items.getAt(0).items.getAt(0).ui).toBe('bar-small');
        });

        it("should not use the defaultButtonUI for segmented buttons with defaultUI of 'default' configured on the instance", function() {
            createToolbar({
                defaultButtonUI: 'foo',
                items: [{
                    xtype: 'segmentedbutton',
                    defaultUI: 'default',
                    items: [{
                        text: 'Bar'
                    }]
                }]
            });

            expect(toolbar.items.getAt(0).getDefaultUI()).toBe('default');
            expect(toolbar.items.getAt(0).items.getAt(0).ui).toBe('default-small');
        });
    });

    describe('defaultFieldUI', function() {
        it("should use the defaultFieldUI for child fields with no ui configured on the instance", function() {
            createToolbar({
                defaultFieldUI: 'foo',
                items: [{
                    xtype: 'textfield'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('foo');
        });

        it("should not use the defaultFieldUI for child fields with ui configured on the instance", function() {
            createToolbar({
                defaultFieldUI: 'foo',
                items: [{
                    xtype: 'textfield',
                    ui: 'bar'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('bar');
        });

        it("should not use the defaultFieldUI for child fields with ui of 'default' configured on the instance", function() {
            createToolbar({
                defaultFieldUI: 'foo',
                items: [{
                    xtype: 'textfield',
                    ui: 'default'
                }]
            });

            expect(toolbar.items.getAt(0).ui).toBe('default');
        });
    });

    describe("FocusableContainer", function() {
        it("should be on with buttons", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }, {
                    xtype: 'button'
                }]
            });

            expect(toolbar.isFocusableContainerActive()).toBeTruthy();
        });

        it("should be off with input fields", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }, {
                    xtype: 'textfield'
                }]
            });

            expect(toolbar.isFocusableContainerActive()).toBeFalsy();
        });

        it("should be off with sliders", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }, {
                    xtype: 'slider'
                }]
            });

            expect(toolbar.isFocusableContainerActive()).toBeFalsy();
        });

        describe("forced to true", function() {
            beforeEach(function() {
                createToolbar({
                    focusableContainer: true,
                    items: [{
                        xtype: 'button'
                    }, {
                        xtype: 'textfield'
                    }]
                });
            });

            it("should activate container", function() {
                expect(toolbar.isFocusableContainerActive()).toBeTruthy();
            });

            it("should keep the role of toolbar", function() {
                expect(toolbar).toHaveAttr('role', 'toolbar');
            });
        });
    });

    describe("ARIA", function() {
        it("should have toolbar role with buttons", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }]
            });

            expect(toolbar).toHaveAttr('role', 'toolbar');
        });

        it("should have group role with input fields", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }, {
                    xtype: 'textfield'
                }]
            });

            expect(toolbar).toHaveAttr('role', 'group');
        });

        it("should have group role with sliders", function() {
            createToolbar({
                items: [{
                    xtype: 'button'
                }, {
                    xtype: 'slider'
                }]
            });

            expect(toolbar).toHaveAttr('role', 'group');
        });
    });

    describe('trackMenus', function() {
        itNotTouch('should maintain menu active state when mouseovering sibling buttons when trackMenus is true', function() {
            createToolbar({
                items: [{
                    text: 'Button1',
                    id: 'b1',
                    menu: {
                        items: [{
                            text: 'b1 me1',
                            id: 'b1m1'
                        }]
                    }
                }, {
                    text: 'Button2',
                    id: 'b2'
                }, {
                    text: 'Button3',
                    id: 'b3',
                    menu: {
                        items: [{
                            text: 'b3 me1',
                            id: 'b3m1'
                        }]
                    }
                }]
            });
            var b1 = toolbar.down('#b1'),
                b2 = toolbar.down('#b2'),
                b3 = toolbar.down('#b3'),
                m1 = b1.getMenu(),
                m3 = b3.getMenu();

            jasmine.fireMouseEvent(b1.el, 'mouseover');
            jasmine.fireMouseEvent(b1.el, 'click');

            // Click shows menu
            waitsFor(function() {
                return m1.isVisible(true);
            });

            // Moving over a menuless button does nothing
            runs(function() {
                jasmine.fireMouseEvent(b1.el, 'mouseout');
                jasmine.fireMouseEvent(b2.el, 'mouseover');
            });

            // Nothing must happen. We cannot wait for anything
            waits(100);

            // Moving over another button with menu shows that button's menu
            runs(function() {
                expect(m1.isVisible(true)).toBe(true);
                jasmine.fireMouseEvent(b2.el, 'mouseout');
                jasmine.fireMouseEvent(b3.el, 'mouseover');
            });

            waitsFor(function() {
                return m3.isVisible(true);
            });

        });

        itNotTouch("should not hide button menu when trackMenus is false", function() {
            createToolbar({
                trackMenus: false,
                items: [{
                    text: 'Button1',
                    id: 'b1',
                    menu: {
                        items: [{
                            text: 'b1 me1',
                            id: 'b1m1'
                        }]
                    }
                }, {
                    text: 'Button2',
                    id: 'b2'
                }, {
                    text: 'Button3',
                    id: 'b3',
                    menu: {
                        items: [{
                            text: 'b3 me1',
                            id: 'b3m1'
                        }]
                    }
                }]
            });

            var b1 = toolbar.down('#b1'),
                b2 = toolbar.down('#b2'),
                b3 = toolbar.down('#b3'),
                m1 = b1.getMenu(),
                m3 = b3.getMenu(),
                i1 = m1.down('menuitem');

            jasmine.fireMouseEvent(b1.el, 'mouseover');
            jasmine.fireMouseEvent(b1.el, 'click');

            // Click shows menu
            waitsFor(function() {
                return m1.isVisible(true);
            }, 'menu 1 to show');

            // Exiting the button hides the menu when trackMenus is false,
            // unless mouseover was to the menu itself
            runs(function() {
                jasmine.fireMouseEvent(b1.el, 'mouseout');
                jasmine.fireMouseEvent(i1.el, 'mouseenter');
                jasmine.fireMouseEvent(i1.el, 'mouseover');
            });

            waitsFor(function() {
                return !!m1.containsFocus;
            }, 'menu 1 to contain focus');

            // Timer is set to 50 ms, give it a bit of slack
            waits(100);

            // Menu should not hide
            runs(function() {
                expect(m1.isVisible(true)).toBe(true);

                jasmine.fireMouseEvent(i1.el, 'mouseout');
                jasmine.fireMouseEvent(m1.el, 'mouseout');
                jasmine.fireMouseEvent(b3.el, 'mouseenter');
                jasmine.fireMouseEvent(b3.el, 'mouseover');
            });

            waits(100);

            // Nothing should have changed w/r/t menus
            runs(function() {
                expect(b3.el).toHaveCls('x-btn-over');
                expect(m1.isVisible(true)).toBe(true);
                expect(m3.isVisible(true)).toBe(false);
            });
        });
    });
});
