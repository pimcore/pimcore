topSuite("Ext.ZIndexManager",
    ['Ext.window.*', 'Ext.grid.Panel', 'Ext.form.field.*', 'Ext.Button', 'Ext.grid.plugin.CellEditing'],
function() {
    function cancelFocus() {
        var task = Ext.focusTask;

        if (task) {
            task.cancel();
        }
    }

    describe("z-index stacking", function() {
        var c1, c2, c3, c4;

        beforeEach(function() {
            c1 = new Ext.window.Window({
                title: 'c1',
                id: 'c1',
                height: 100,
                width: 100,
                focusOnToFront: false
            });
            c2 = new Ext.window.Window({
                title: 'c2',
                id: 'c2',
                height: 100,
                width: 100,
                focusOnToFront: false
            });
            c3 = new Ext.window.Window({
                title: 'c3',
                id: 'c3',
                height: 100,
                width: 100,
                focusOnToFront: false
            });
            c4 = new Ext.window.Window({
                title: 'c4',
                id: 'c4',
                height: 100,
                width: 100,
                modal: true
            });
        });

        afterEach(function() {
            Ext.destroy(c1, c2, c3, c4);
        });

        it("should order the windows as they are rendered", function() {
            c1.show();
            c2.show();
            c3.show();
            // onMousedown quits if there is a pending focus task.
            cancelFocus();

            // Order bottom up should be c1, c2, c3
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            expect(c2.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
        });

        it("should re-order the windows on mousedown", function() {
            c1.showAt(0, 0);
            c2.showAt(50, 0);
            c3.showAt(100, 0);
            // onMousedown quits if there is a pending focus task.
            cancelFocus();

            // Fake mousedown because sythetic events won't work with capture which is what Floating's listener uses
            c1.onMouseDown({
                target: c1.el.dom
            });

            // Mousedown moves to top
            // Order bottom up should be c2, c3, c1
            expect(c1.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
        });

        it("should honour alwaysOnTop", function() {
            c1.setAlwaysOnTop(true);
            c1.showAt(0, 0);
            c2.showAt(50, 0);
            c3.showAt(100, 0);
            // onMousedown quits if there is a pending focus task.
            cancelFocus();

            // c1 should be glued to the top
            // Order bottom up should be c2, c3, c1
            expect(c1.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());

            // Fake mousedown because sythetic events won't work with capture which is what Floating's listener uses
            c2.onMouseDown({
                target: c2.el.dom
            });

            // c2 should have gone up as far as it can to just below the alwaysOnTop c1
            // Order bottom up should now be c3, c2, c1
            expect(c1.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            expect(c2.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());

        });

        it("should sort to the bottom of the ZIndexStack if alwaysOnTop === -1", function() {
            c3.setAlwaysOnTop(-1);
            c1.showAt(0, 0);
            c2.showAt(50, 0);
            c3.showAt(100, 0);
            // onMousedown quits if there is a pending focus task
            cancelFocus();

            // c3 should be glued to the bottom
            // Order bottom up should be c3, c1, c2
            expect(c2.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
            expect(c1.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());
        });

        it("should move to the front as far as possible while respecting other alwaysOnTop components", function() {
            c4.setAlwaysOnTop(1); // This will always be second from top
            c4.modal = false;
            c3.setAlwaysOnTop(2); // This will always be topmost
            c1.show();
            c2.show();
            c3.show();
            c4.show();
            // onMousedown quits if there is a pending focus task
            cancelFocus();

            // It cannot go all the way to front because there are two alwaysOnTop
            // Windows which should be above it.
            c1.toFront();

            // Order bottom up should c2, c1, c4, c3
            expect(c1.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            expect(c4.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
            expect(c3.el.getZIndex()).toBeGreaterThan(c4.el.getZIndex());
        });

        it("should order parents", function() {
            // c2's ZIndexManager now manages C3's zIndex
            c2.add(c3);

            c1.showAt(0, 0);
            c2.showAt(50, 0);
            c3.showAt(25, 25);
            // onMousedown quits if there is a pending focus task.
            cancelFocus();

            // Fake mousedown because sythetic events won't work with capture which is what Floating's listener uses
            c1.onMouseDown({
                target: c1.el.dom
            });

            // c1 moved to top
            // Order bottom up should be c2, c3, c1
            expect(c1.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            // Fake mousedown because sythetic events won't work with capture which is what Floating's listener uses
            c2.onMouseDown({
                target: c2.el.dom
            });

            // onMousedown quits if there is a pending focus task.
            cancelFocus();

            // Mousedown on c2 should bring both c2 and c2 above c1 because c3 is a child of c2
            // Order bottom up should be c1, c2, c3
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            expect(c2.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
        });

        it("should assign a z-index to a floater that is rendered visible", function() {
            var c = new Ext.Component({
                renderTo: Ext.getBody(),
                floating: true,
                html: 'Foo'
            });

            c.show();
            expect(c.getEl().getZIndex()).toBe(Ext.WindowManager.zseed);
            c.destroy();
        });

        it("should always keep modal maks behind the window", function() {
            c1.show();
            c4.show();
            c1.destroy();

            expect(c4.zIndexManager.mask.getZIndex()).toBeLessThan(c4.el.getZIndex());
        });

        (Ext.supports.Touch ? xit : it)('should maintain stacking order upon close os topmost window', function() {
            c1.showAt(0, 0);
            c2.showAt(20, 20);
            c3.showAt(40, 40);
            c4.showAt(60, 60);

            jasmine.fireMouseEvent(c4.tools.close.el, 'mousedown');

            expect(c2.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
            expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            expect(c4.el.getZIndex()).toBeGreaterThan(c3.el.getZIndex());

            jasmine.fireMouseEvent(c4.tools.close.el, 'mouseup');
            jasmine.fireMouseEvent(c4.tools.close.el, 'click');

            waitsFor(function() {
                return c4.destroyed;
            });

            runs(function() {
                expect(c2.el.getZIndex()).toBeGreaterThan(c1.el.getZIndex());
                expect(c3.el.getZIndex()).toBeGreaterThan(c2.el.getZIndex());
            });
        });
    });

    describe("modal masking", function() {
        var w, mask;

        afterEach(function() {
            Ext.destroy(w);
            mask = w = null;
        });

        function makeWindow(config) {
            w = new Ext.window.Window(Ext.applyIf(config || {}, {
                width: 200,
                height: 200,
                title: 'Foo',
                autoShow: true,
                modal: true
            }));
        }

        describe("mask visibility", function() {
            beforeEach(function() {
                makeWindow();
                mask = w.zIndexManager.mask;
            });

            function getMaskIndex() {
                return parseInt(w.zIndexManager.mask.getStyle('z-index'), 10);
            }

            it("should show the mask below the floater when open", function() {
                expect(mask.isVisible()).toBe(true);
                expect(getMaskIndex()).toBeLessThan(w.getEl().getZIndex());
            });

            it("should re-show the mask after hiding then showing", function() {
                w.hide();
                w.show();
                expect(mask.isVisible()).toBe(true);
            });

            it("should hide the modal mask when hiding the last floater", function() {
                w.hide();
                expect(mask.isVisible()).toBe(false);
            });

            it("should hide the modal mask when destroying the last floater", function() {
                w.destroy();
                expect(mask.isVisible()).toBe(false);
            });
        });

        describe("element tabbability", function() {
            var btn, tabbables;

            beforeEach(function() {
                btn = new Ext.button.Button({
                    renderTo: Ext.getBody(),
                    text: 'foo'
                });
            });

            afterEach(function() {
                Ext.destroy(btn);
                btn = null;
            });

            describe("components below mask", function() {
                beforeEach(function() {
                    makeWindow();
                });

                it("button should become untabbable on mask show", function() {
                    // skipSelf = true, skipChildren = false, excludeRoot = w.el
                    tabbables = Ext.getBody().findTabbableElements({
                        skipSelf: true,
                        excludeRoot: w.el
                    });

                    expect(tabbables.length).toBe(0);
                });

                it("button should become tabbable on mask hide", function() {
                    w.hide();

                    // skipSelf = true, skipChildren = false, excludeRoot = w.el
                    tabbables = Ext.getBody().findTabbableElements({
                        skipSelf: true,
                        excludeRoot: w.el
                    });

                    expect(tabbables).toEqual([ btn.getFocusEl().dom ]);
                });

                it("button should become untabbable on mask show/hide/show", function() {
                    w.hide();
                    w.show();

                    // skipSelf = true, skipChildren = false, excludeRoot = w.el
                    tabbables = Ext.getBody().findTabbableElements({
                        skipSelf: true,
                        excludeRoot: w.el
                    });

                    expect(tabbables.length).toBe(0);
                });
            });

            describe("components above mask", function() {
                beforeEach(function() {
                    makeWindow({
                        items: [
                            { xtype: 'textfield', fieldLabel: 'Login' },
                            { xtype: 'textfield', fieldLabel: 'Password' }
                        ],

                        buttons: [
                            { text: 'OK' }
                        ]
                    });
                });

                it("should keep items above the mask tabbable", function() {
                    tabbables = w.getEl().findTabbableElements({
                        skipSelf: true
                    });

                    // 6 tababbles:
                    // - Top focus trap
                    // - textfield 1
                    // - textfield 2
                    // - Button
                    // - Bottom focus trap
                    expect(tabbables.length).toBe(5);
                });
            });
        });

        describe("mask size", function() {
            // Not sure how to simulate a body resize here
            it("should size the mask to the body if the manager is global", function() {
                makeWindow();
                mask = w.zIndexManager.mask;
                expect(mask.getSize()).toEqual(Ext.Element.getViewSize());
                expect(mask).toHaveCls(Ext.Component.prototype.borderBoxCls);
            });

            it("should set the mask to the size of the container", function() {
                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400
                });

                w = ct.add({
                    xtype: 'window',
                    width: 100,
                    height: 100,
                    modal: true,
                    title: 'Foo'
                });

                w.show();
                mask = w.zIndexManager.mask;
                expect(mask.getSize()).toEqual({
                    width: 400,
                    height: 400
                });
                ct.destroy();
            });

            // This currently doesn't work, but probably should
            xit("should resize the mask when the container resizes", function() {
                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400
                });

                w = ct.add({
                    xtype: 'panel',
                    floating: true,
                    width: 100,
                    height: 100,
                    modal: true,
                    title: 'Foo'
                });

                w.show();
                mask = w.zIndexManager.mask;
                ct.setSize(200, 200);
                expect(mask.getSize()).toEqual({
                    width: 200,
                    height: 200
                });
                ct.destroy();
            });
        });
    });

    describe("hide/show", function() {
        it("should restore just visible items", function() {
            var a = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var b = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var c = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            a.hide();
            expect(a.isVisible()).toBe(false);
            expect(b.isVisible()).toBe(true);
            expect(c.isVisible()).toBe(true);

            Ext.WindowManager.hide();
            expect(a.isVisible()).toBe(false);
            expect(b.isVisible()).toBe(false);
            expect(c.isVisible()).toBe(false);

            Ext.WindowManager.show();
            expect(a.isVisible()).toBe(false);
            expect(b.isVisible()).toBe(true);
            expect(c.isVisible()).toBe(true);

            Ext.destroy(a, b, c);
        });
    });

    describe("hideAll", function() {
        it("should hide all visible items", function() {
            var a = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var b = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var c = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            Ext.WindowManager.hideAll();
            expect(a.isVisible()).toBe(false);
            expect(b.isVisible()).toBe(false);
            expect(c.isVisible()).toBe(false);

            Ext.destroy(a, b, c);
        });

        it("should hide the mask", function() {
            var a = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var b = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var c = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true,
                modal: true
            });

            expect(Ext.WindowManager.mask.isVisible()).toBe(true);
            Ext.WindowManager.hideAll();
            expect(Ext.WindowManager.mask.isVisible()).toBe(false);

            Ext.destroy(a, b, c);
        });

        it("should be able to show/hide the modal mask after a hideAll call", function() {
            var a = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var b = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            var c = new Ext.window.Window({
                width: 100,
                height: 100,
                autoShow: true
            });

            Ext.WindowManager.hideAll();
            expect(a.isVisible()).toBe(false);
            expect(b.isVisible()).toBe(false);
            expect(c.isVisible()).toBe(false);

            var mask = a.zIndexManager.mask;

            expect(mask.isVisible()).toBe(false);

            var d = new Ext.window.Window({
                width: 100,
                height: 100,
                modal: true,
                autoShow: true
            });

            expect(mask.isVisible()).toBe(true);
            d.hide();
            expect(mask.isVisible()).toBe(false);

            Ext.destroy(a, b, c, d);
        });
    });

    // testcase for grid in a modal windows showing MessageBox on edit
    describe("Grid with modal windows and MessageBox", function() {
        var win, grid, cell, plugin;

        beforeEach(function() {
            win = new Ext.window.Window({
                items: [{
                    xtype: 'grid',
                    columns: [{
                        dataIndex: 'f1',
                        editor: {}
                    }],
                    store: {
                        data: [{
                            f1: 'edit me'
                        }]
                    },
                    plugins: {
                        ptype: 'cellediting'
                    }
                }],
                width: 400,
                height: 130,
                modal: true
            }).show();

            grid = win.down('grid');
            plugin = grid.findPlugin('cellediting');
        });

        afterEach(function() {
            win.close();
            Ext.MessageBox.hide();
            win.destroy();
            win = grid = cell = null;
        });

        it("should display the MessageBox on top", function() {
            grid.on('edit', function() {
                Ext.Msg.confirm('Foo', 'Bar');
            });
            cell = grid.getView().getCellInclusive({
                row: 0,
                column: 0
            }, true);

            jasmine.fireMouseEvent(cell, 'dblclick');

            waitsForFocus(plugin.activeEditor.field, 'plugin to edit');

            runs(function() {
                jasmine.fireKeyEvent(Ext.Element.getActiveElement(), 'keydown', Ext.event.Event.ENTER);
            });

            waitsFor(function() {
                return Ext.MessageBox.isVisible();
            }, 'message box to become visible');

            runs(function() {
                expect(Ext.MessageBox.el.getZIndex()).toBeGreaterThan(win.el.getZIndex());
            });
        });
    });

    // testcase for https://sencha.jira.com/browse/EXTJS-14046
    describe("picker field's pickers should stick to back if alwaysOnTop is set to -1", function() {
        it("should keep pickers below all other floating components", function() {
            var windowCombo,
                combo = new Ext.form.ComboBox({
                    store: ['A', 'b', 'C'],
                    editable: false,
                    fieldLabel: 'Combo',
                    renderTo: Ext.getBody(),
                    listConfig: {
                        alwaysOnTop: -1
                    }
                }),
                dateField = new Ext.form.Date({
                    editable: false,
                    fieldLabel: 'Date',
                    renderTo: Ext.getBody()
                }),
                window = new Ext.window.Window({
                    autoShow: true,
                    title: 'Test',
                    x: 200,
                    y: 0,
                    width: 400,
                    height: 400,
                    items: [
                        windowCombo = new Ext.form.ComboBox({
                            store: ['A', 'b', 'C'],
                            editable: false,
                            listCOnfig: {
                                alwaysOnTop: -1
                            }
                        })
                    ]
                });

            dateField.getPicker().setAlwaysOnTop(-1);

            // The combo dropdown should be below the window
            combo.expand();
            expect(combo.getPicker().el.getZIndex()).toBeLessThan(window.el.getZIndex());
            combo.collapse();

            // The combo dropdown should be below the window
            dateField.expand();
            expect(dateField.getPicker().el.getZIndex()).toBeLessThan(window.el.getZIndex());
            dateField.collapse();

            // The combo dropdown in the window is realtive to the window's ZIndexManager.
            // It should go above the window
            windowCombo.expand();
            expect(windowCombo.getPicker().el.getZIndex()).toBeGreaterThan(window.el.getZIndex());
            windowCombo.collapse();

            // The combo dropdown should be below the window
            combo.expand();
            expect(combo.getPicker().el.getZIndex()).toBeLessThan(window.el.getZIndex());

            Ext.destroy(combo, dateField, window);
        });
    });

    describe("bringToFront", function() {
        it("should return false when bringing to front a non-rendered window, when passing id", function() {
            var win = new Ext.window.Window({
                title: 'Win',
                id: 'theWin',
                width: 100,
                height: 100,
                autoShow: true
            });

            expect(Ext.WindowManager.bringToFront('theWin')).toBe(false);
            win.destroy();
        });

        it("should return false when bringing to front a componwnt we do not own", function() {
            var win = new Ext.window.Window({
                title: 'Win',
                id: 'theWin',
                width: 100,
                height: 100
            });

            // It is not rendered, so will not have regsitered with the default ZIndexManager.
            // So asking for it to be moved to front should return false.
            expect(Ext.WindowManager.bringToFront(win)).toBe(false);
            win.destroy();
        });
    });

    // This test would better fit a Floating test suite but it's not clear
    // where the concerns are separated since Floating code is private
    // and is supposed to be called by ZIndexManager only. So let it be here.
    describe("focus handling", function() {
        var focusAndWait = jasmine.focusAndWait,
            waitForFocus = jasmine.waitForFocus,
            expectFocused = jasmine.expectFocused,
            btn, win, input1, input2;

        beforeEach(function() {
            btn = new Ext.button.Button({
                renderTo: Ext.getBody(),
                text: 'foo'
            });

            win = new Ext.window.Window({
                title: 'bar',
                width: 200,
                height: 100,
                x: 30,
                y: 30,
                closeAction: 'hide',

                items: [{
                    xtype: 'textfield',
                    itemId: 'input1'
                }, {
                    xtype: 'textfield',
                    itemId: 'input2',
                    allowBlank: false
                }]
            });

            input1 = win.down('#input1');
            input2 = win.down('#input2');

            focusAndWait(btn);
        });

        afterEach(function() {
            Ext.destroy(win, btn);

            btn = win = input1 = input2 = null;
        });

        describe("focusable floater show/hide with no animation", function() {
            beforeEach(function() {
                win.show();

                waitForFocus(win);
            });

            it("should focus the window on show", function() {
                expectFocused(win);
            });

            it("should focus the button back on window hide", function() {
                win.close();

                expectFocused(btn);
            });
        });

        describe("focusable floater show/hide with animation", function() {
            beforeEach(function() {
                win.show(btn);

                waitForFocus(win);
            });

            it("should focus the window on show", function() {
                expectFocused(win);
            });

            it("should focus the button back on window hide", function() {
                win.close(btn);

                expectFocused(btn);
            });
        });

        describe("non-focusable floater show/hide", function() {
            var panel;

            beforeEach(function() {
                panel = new Ext.panel.Panel({
                    floating: true,
                    title: 'floating',
                    width: 100,
                    height: 100,
                    x: 300,
                    y: 30,
                    html: 'floating panel'
                });

                win.show();

                focusAndWait(input2);
            });

            afterEach(function() {
                Ext.destroy(panel);
            });

            it("should not steal focus on floater show", function() {
                panel.show();

                expectFocused(input2);
            });

            it("should not munge focus on floater hide", function() {
                panel.show();
                panel.hide();

                expectFocused(input2);
            });
        });

        describe("event order", function() {
            it("should fire floater hide event after sorting zIndexStack", function() {
                var oldOnCollectionSort = Ext.WindowManager.onCollectionSort,
                    events = [],
                    win;

                win = new Ext.window.Window({
                    title: 'foo',
                    width: 300,
                    height: 200,
                    x: 10,
                    y: 10,
                    closeAction: 'hide',
                    listeners: {
                        hide: function() {
                            events.push('hide');
                        }
                    }
                }).show();

                // Can't use jasmine spy here because it can't be chained
                Ext.WindowManager.onCollectionSort = function() {
                    events.push('sort');
                    oldOnCollectionSort.call(Ext.WindowManager);
                };

                // Event flow is synchronous here
                win.close();

                expect(events).toEqual(['sort', 'hide']);

                // Fall back to the prototype
                delete Ext.WindowManager.onCollectionSort;

                win.destroy();

                win = null;
            });
        });
    });

    describe("focus restoration after window drag", function() {
        var win;

        afterEach(function() {
            win.destroy();
            win = null;
        });

        it("should restore focus after showing", function() {
            var xy, headerXY, x, y, child, text;

            win = new Ext.window.Window({
                title: 'Test Window',
                width: 410,
                height: 400,
                x: 0, y: 0,
                items: child = new Ext.window.Window({
                    width: 200,
                    height: 100,
                    items: {
                        xtype: 'textfield'
                    }
                })
            });

            text = child.items.first();

            win.show();

            jasmine.waitForFocus(win, 'top window to focus');

            runs(function() {
                child.show();
            });

            jasmine.waitForFocus(child, 'child window to focus');

            jasmine.focusAndWait(text, text, 'text field within child window to focus');

            runs(function() {
                xy = win.getXY();
                headerXY = win.header.el.getAnchorXY('c');
                x = headerXY[0];
                y = headerXY[1];

                expect(text.hasFocus).toBe(true);
                // Drag the Window by the header
                jasmine.fireMouseEvent(win.header.el, 'mousedown', x, y);
                jasmine.fireMouseEvent(Ext.getBody(), 'mousemove', x + 100, y);
            });

            waits(100);

            runs(function() {
                expect(child.isVisible()).toBe(false);

                jasmine.fireMouseEvent(Ext.getBody(), 'mouseup', x + 100, y);
            });

            runs(function() {
                // Window should have moved 100px right
                xy[0] += 100;
                expect(win.getXY()).toEqual(xy);
                expect(text.hasFocus).toBe(true);
            });
        });
    });

    describe("Menu with modal window and MessageBox", function() {
        it("should render the menu after the MessageBox has been closed", function() {
            var container = Ext.create('Ext.Container', {
                    items: [{
                        xtype: 'button',
                        itemId: 'menu-button',
                        text: 'Menu',
                        menu: {
                            xtype: 'menu',
                            items: [{
                                text: 'Foo'
                            }]
                        }
                    }, {
                        xtype: 'button',
                        itemId: 'modal-button',
                        text: 'Open modal',
                        handler: function() {
                            msg = Ext.Msg.alert('Foo', 'Bar');
                        }
                    }],

                    renderTo: document.body,
                    width: 300
                }),
                msg, menuBtn, modalBtn;

            menuBtn = container.getComponent('menu-button');
            modalBtn = container.getComponent('modal-button');

            // necessary for having another ZIndex'd component in the stack
            var win = Ext.create('Ext.window.Window', {
                title: 'MyWindow',
                width: 100,
                height: 100
            }).show();

            // click the menu to expand and then button to display the MessageBox,
            // then click the first visible button to close
            runs(function() {
                jasmine.fireMouseEvent(menuBtn.el, 'click');
                jasmine.fireMouseEvent(modalBtn.el, 'click');

                var btn = Ext.Array.findBy(msg.msgButtons, function(btn) { return btn.isVisible(); });

                jasmine.fireMouseEvent(btn.el, 'click');
            });
            waits(500);

            // clicking the menu again should make it visible in the stack
            runs(function() {
                jasmine.fireMouseEvent(menuBtn.el, 'click');
                expect(menuBtn.menu.isVisible()).toBe(true);

                container = win = Ext.destroy(container, win);
            });
        });
    });
});
