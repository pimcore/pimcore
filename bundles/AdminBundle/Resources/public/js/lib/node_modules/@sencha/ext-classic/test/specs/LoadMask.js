topSuite("Ext.LoadMask", ['Ext.grid.Panel', 'Ext.button.Button'], function() {
    var mask, target, mockComplete;

    beforeEach(function() {
        MockAjaxManager.addMethods();
    });

    function makeTarget(targetCfg) {
        target = Ext.widget(targetCfg && targetCfg.xtype || 'component', Ext.apply({
            width: 100,
            height: 100,
            renderTo: Ext.getBody()
        }, targetCfg));
    }

    function createMask(cfg, targetCfg) {
       if (!target) {
           makeTarget(targetCfg);
       }

       mask = new Ext.LoadMask(Ext.apply({ target: target }, cfg));

       return mask;
    }

    afterEach(function() {
        Ext.destroy(target, mask);
        mask = target = null;
        MockAjaxManager.removeMethods();
    });

    describe("mask options", function() {
        describe("msg", function() {
            it("should default the message to Loading...", function() {
                createMask().show();
                expect(mask.msgTextEl.dom.innerHTML).toEqual('Loading...');
            });

            it("should accept a custom message", function() {
                createMask({
                    msg: 'Foo'
                }).show();
                expect(mask.msgTextEl.dom.innerHTML).toEqual('Foo');
            });
        });

        describe("msgCls", function() {
            it("should default to x-mask-loading", function() {
                createMask().show();
                expect(mask.msgEl.hasCls('x-mask-loading')).toBe(true);
            });

            it("should accept a custom class", function() {
                createMask({
                    msgCls: 'foo'
                }).show();
                expect(mask.msgEl.hasCls('foo')).toBe(true);
            });
        });

        describe("msgWrapCls", function() {
            it("should default to x-mask-msg", function() {
                createMask().show();
                expect(mask.msgWrapEl.hasCls('x-mask-msg')).toBe(true);
            });

            it("should accept a custom class", function() {
                createMask({
                    msgWrapCls: 'foo'
                }).show();
                expect(mask.msgWrapEl.hasCls('foo')).toBe(true);
            });

            it("should accept legacy maskCls config", function() {
                // Deprecated warning is expected
                spyOn(Ext.log, 'warn');

                createMask({
                    maskCls: 'foo'
                }).show();

                expect(mask.msgWrapEl.hasCls('foo')).toBe(true);
            });

            it("should favor msgWrapCls over maskCls if both are present", function() {
                // Deprecated warning is expected
                spyOn(Ext.log, 'warn');

                createMask({
                    maskCls: 'foo',
                    msgWrapCls: 'bar'
                }).show();

                expect(mask.msgWrapEl.hasCls('bar')).toBe(true);
                expect(mask.msgWrapEl.hasCls('foo')).toBe(false);
            });
        });

        describe("useMsg", function() {
            it("should default to true", function() {
                createMask().show();
                expect(mask.el.isVisible()).toBe(true);
            });

            it("should respect the useMsg: false", function() {
                createMask({
                    useMsg: false
                }).show();
                expect(mask.msgWrapEl.isVisible()).toBe(false);
            });

            it("should should still show the mask even when useMsg: false", function() {
                createMask({
                    useMsg: false
                }).show();
                expect(mask.el.isVisible()).toBe(true);
            });
        });

        describe("useTargetEl", function() {
            it("should size to the targetEl & should default to false", function() {
                createMask().show();
                var size = mask.el.getSize();

                expect(size.width).toBe(100);
                expect(size.height).toBe(100);
            });

            it("should size to the targetEl when useTargetEl: true", function() {
                createMask({
                    useTargetEl: true
                }, {
                    xtype: 'panel',
                    width: 100,
                    height: 100,
                    renderTo: Ext.getBody(),
                    title: 'Title'
                }).show();

                var size = mask.el.getSize(),
                    bodySize = target.body.getViewSize();

                expect(size.width).toBe(bodySize.width);
                expect(size.height).toBe(bodySize.height);
            });
        });

    });

    describe("z-index on show", function() {
        describe("with floating", function() {
            it("should have a higher z-index than the floater when used directly on a floater", function() {
                createMask(null, {
                    floating: true
                });
                target.show();
                mask.show();
                expect(mask.getEl().getZIndex()).toBeGreaterThan(target.getEl().getZIndex());
            });

            it("should have a higher z-index than the floater when used on a direct child of a floater", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    width: 100,
                    height: 100,
                    items: {
                        xtype: 'component'
                    }
                });

                ct.show();
                target = ct.items.first();
                createMask();
                mask.show();
                expect(mask.getEl().getZIndex()).toBeGreaterThan(ct.getEl().getZIndex());
                ct.destroy();
            });

            it("should have a higher z-index than the floater when used on a deep child of a floater", function() {
                var ct = new Ext.container.Container({
                    floating: true,
                    width: 100,
                    height: 100,
                    items: {
                        xtype: 'container',
                        items: {
                            xtype: 'container',
                            items: {
                                xtype: 'container',
                                items: {
                                    xtype: 'container',
                                    items: {
                                        xtype: 'component',
                                        itemId: 'foo'
                                    }
                                }
                            }
                        }
                    }
                });

                ct.show();
                target = ct.down('#foo');
                createMask();
                mask.show();
                expect(mask.getEl().getZIndex()).toBeGreaterThan(ct.getEl().getZIndex());
                ct.destroy();
            });
        });
    });

    describe("updating to target changes", function() {
        describe("root level component", function() {
            describe("floating target", function() {
                beforeEach(function() {
                    target = new Ext.Component({
                        floating: true,
                        width: 100,
                        height: 100,
                        x: 100,
                        y: 100
                    });
                    target.show();
                });

                it("should set the position of the mask to match the floater", function() {
                    createMask().show();

                    var xy = mask.el.getXY();

                    expect(xy[0]).toBe(100);
                    expect(xy[1]).toBe(100);
                });

                it("should change the position when the component moves", function() {
                    createMask().show();
                    target.setPosition(200, 200);

                    var xy = mask.el.getXY();

                    expect(xy[0]).toBe(200);
                    expect(xy[1]).toBe(200);
                });
            });

            describe("sizing", function() {
                it("should update the mask size when the component resizes", function() {
                    createMask().show();
                    target.setSize(150, 200);

                    var size = mask.el.getSize();

                    expect(size.width).toBe(150);
                    expect(size.height).toBe(200);
                });

                it("should update the mask size to the targetEl when the component resizes", function() {
                    createMask({
                        useTargetEl: true
                    }, {
                        xtype: 'panel',
                        renderTo: Ext.getBody(),
                        width: 100,
                        height: 100,
                        title: 'Title'
                    }).show();
                    target.setSize(150, 200);

                    var size = mask.el.getSize(),
                        bodySize = target.body.getViewSize();

                    expect(size.width).toBe(bodySize.width);
                    expect(size.height).toBe(bodySize.height);
                });
            });

            describe("hide/show", function() {
                it("should hide the mask when the component is hidden", function() {
                    createMask(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    }).show();
                    target.hide();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should re-show the mask when toggling the hidden state", function() {
                    createMask(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    }).show();
                    target.hide();
                    target.show();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should not show the mask if it's hidden during a toggle", function() {
                    createMask(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    }).show();
                    target.hide();
                    mask.hide();
                    target.show();
                    expect(mask.isVisible()).toBe(false);
                });
            });

            describe("disable/enable", function() {
                it("should not show the loadMask when loading a store if the mask is disabled", function() {
                    var menu, menuItem, panelMask, store,
                    panel = new Ext.grid.Panel({
                        renderTo: document.body,
                        title: 'Test focus',
                        height: 300,
                        width: 600,
                        store: {
                            asynchronousLoad: false,
                            proxy: {
                                type: 'ajax',
                                url: 'foo'
                            }
                        },
                        loadMask: true,
                        columns: [{
                            text: 'Columns one',
                            width: 200
                        }, {
                            text: 'Column two',
                            flex: 1
                        }]
                    });

                    store = panel.store;
                    store.load();

                    waitsFor(function() {
                        panelMask = panel.view.loadMask;

                        return panelMask.isLoadMask;
                    }, 'Store not loaded');

                    runs(function() {
                        spyOn(panelMask, 'show').andCallThrough();
                        expect(panelMask.show.callCount).toBe(0);

                        panelMask.setDisabled(true);
                        store.load();
                        panelMask.setDisabled(false);

                        store.load();
                        expect(panelMask.show.callCount).toBe(1);
                        panel.destroy();

                    });
                });
            });

            describe("expand/collapse", function() {
                beforeEach(function() {
                    target = new Ext.panel.Panel({
                        width: 100,
                        height: 100,
                        renderTo: Ext.getBody(),
                        collapsible: true,
                        animCollapse: false,
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                });

                it("should hide the mask when the component is collapsed", function() {
                    createMask().show();
                    target.collapse();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should re-show the mask after expanding", function() {
                    createMask().show();
                    target.collapse();
                    target.expand();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should not show the mask if it's hidden during a toggle", function() {
                    createMask().show();
                    target.collapse();
                    mask.hide();
                    target.expand();
                    expect(mask.isVisible()).toBe(false);
                });
            });

            describe("focus handling", function() {
                var waitForFocus = jasmine.waitForFocus,
                    expectFocused = jasmine.expectFocused,
                    fooBtn, barBtn, panel;

                beforeEach(function() {
                    target = new Ext.panel.Panel({
                        width: 100,
                        height: 100,
                        renderTo: Ext.getBody(),
                        items: [{
                            xtype: 'button',
                            text: 'foo'
                        }]
                    });

                    createMask();

                    fooBtn = target.down('button');

                    fooBtn.focus();

                    waitForFocus(fooBtn);
                });

                afterEach(function() {
                    Ext.destroy(barBtn, panel);
                });

                it('should not cause onFocusLeave consequences on show', function() {
                    var menu, menuItem, panelMask, panelStore, col0;

                    panel = new Ext.grid.Panel({
                        renderTo: document.body,
                        title: 'Test focus',
                        height: 300,
                        width: 600,
                        store: {
                            proxy: {
                                type: 'ajax',
                                url: 'foo'
                            }
                        },
                        loadMask: true,
                        columns: [{
                            text: 'Columns one',
                            width: 200,
                            locked: true
                        }, {
                            text: 'Column two',
                            flex: 1
                        }]
                    });
                    panelStore = panel.store;
                    col0 = panel.getVisibleColumnManager().getColumns()[0];

                    Ext.testHelper.showHeaderMenu(col0);

                    runs(function() {
                        menu = col0.activeMenu;
                        menuItem = menu.child(':first');
                        menuItem.focus(false, true);
                        waitsForFocus(menuItem);
                    });

                    runs(function() {
                        // Show the mask and it should focus
                        panelStore.fireEvent('beforeload', panelStore);
                    });
                    waitsFor(function() {
                        panelMask = panel.view.loadMask;

                        return panelMask && panelMask.isVisible();
                    }, 'LoadMask to show');

                    // That should NOT have disturbed the floating Menu which hides onFocusLeave
                    runs(function() {
                        expect(menu.isVisible()).toBe(true);
                        expect(menuItem.hasFocus).toBe(true);
                        panelStore.fireEvent('load', panelStore);
                    });

                    waitsFor(function() {
                        return !panelMask.isVisible();
                    }, 'LoadMask to hide');

                    // Focus must revert back into the menu
                    runs(function() {
                        expect(menuItem.hasFocus).toBe(true);
                    });
                });

                it("should steal focus from within target on show", function() {
                    mask.show();

                    waitForFocus(mask);

                    expectFocused(mask);
                });

                describe("restoring focus on hide", function() {
                    beforeEach(function() {
                        mask.show();

                        waitForFocus(mask);
                    });

                    it("should go to previously focused element", function() {
                        mask.hide();

                        waitForFocus(fooBtn);

                        expectFocused(fooBtn);
                    });

                    it("should not restore focus if mask el is not focused", function() {
                        barBtn = new Ext.button.Button({
                            renderTo: Ext.getBody(),
                            text: 'bar'
                        });

                        barBtn.focus();

                        waitForFocus(barBtn);

                        runs(function() {
                            mask.hide();
                        });

                        waitForFocus(barBtn);

                        expectFocused(barBtn);
                    });
                });
            });
        });

        describe("in a container", function() {
            var ct1, ct2, makeCt;

            beforeEach(function() {
                makeCt = function(floating) {

                    target = new Ext.Component({
                        getMaskTarget: function() {
                            return null;
                        }
                    });

                    ct2 = new Ext.panel.Panel({
                        animCollapse: false,
                        title: 'Title2',
                        collapsible: true,
                        layout: 'fit',
                        items: target
                    });

                    ct1 = new Ext.panel.Panel({
                        animCollapse: false,
                        width: 200,
                        height: 200,
                        floating: floating,
                        title: 'Title1',
                        collapsible: true,
                        renderTo: Ext.getBody(),
                        layout: 'fit',
                        items: ct2,
                        x: floating ? 100 : undefined,
                        y: floating ? 100 : undefined
                    });
                    ct1.show();
                };
            });

            afterEach(function() {
                Ext.destroy(ct1);
                makeCt = ct1 = ct2 = null;
            });
            describe("floating target", function() {
                it("should set the position of the mask to match the floater", function() {
                    makeCt(true);
                    createMask().show();

                    var xy = mask.el.getXY(),
                        compXY = target.getPosition();

                    expect(xy[0]).toBe(compXY[0]);
                    expect(xy[1]).toBe(compXY[1]);
                });

                it("should change the position when the component moves", function() {
                    makeCt(true);
                    createMask().show();
                    ct1.setPosition(200, 200);

                    var xy = mask.el.getXY(),
                        compXY = target.getPosition();

                    expect(xy[0]).toBe(compXY[0]);
                    expect(xy[1]).toBe(compXY[1]);
                });
            });

            describe("sizing", function() {
                it("should update the mask size when the component resizes", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.setSize(250, 300);

                    var size = mask.el.getSize(),
                        compSize = target.getSize();

                    expect(size.width).toBe(compSize.width);
                    expect(size.height).toBe(compSize.height);
                });

            });

            describe("hide/show", function() {
                it("should hide the mask when the top-most container is hidden", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.hide();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should hide the mask when the direct parent container is hidden", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.hide();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should re-show the mask when the top-most container is shown", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.hide();
                    ct1.show();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should re-show the mask when the direct parent container is shown", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.hide();
                    ct2.show();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should not re-show the mask when the mask is hidden during the top-most toggle", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.hide();
                    mask.hide();
                    ct1.show();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should not re-show the mask when the mask is hidden during the parent container toggle", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.hide();
                    mask.hide();
                    ct2.show();
                    expect(mask.isVisible()).toBe(false);
                });
            });

            describe("expand/collapse", function() {

                it("should hide the mask when the top-most container is collapsed", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.collapse();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should hide the mask when the direct parent container is collapsed", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.collapse();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should re-show the mask after the top-most container expands", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.collapse();
                    ct1.expand();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should re-show the mask after the direct parent container expands", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.collapse();
                    ct2.expand();
                    expect(mask.isVisible()).toBe(true);
                });

                it("should not re-show the mask when the mask is hidden during the top-most toggle", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct1.collapse();
                    mask.hide();
                    ct1.expand();
                    expect(mask.isVisible()).toBe(false);
                });

                it("should not re-show the mask when the mask is hidden during the parent container toggle", function() {
                    makeCt(null, {
                        getMaskTarget: function() {
                            return null;
                        }
                    });
                    createMask().show();
                    ct2.collapse();
                    mask.hide();
                    ct2.expand();
                    expect(mask.isVisible()).toBe(false);
                });
            });
        });
    });

    describe("shim", function() {
        it("should not have a shim by default", function() {
            createMask().show();

            expect(mask.el.shim).toBeUndefined();
        });

        it("should have a shim if configured with shim: true", function() {
            createMask({
                shim: true
            }).show();

            expect(mask.el.shim instanceof Ext.dom.Shim).toBe(true);
            expect(mask.el.shim.el.isVisible()).toBe(true);
        });

        it("should have a shim if Ext.useShims is true", function() {
            Ext.useShims = true;

            createMask().show();

            expect(mask.el.shim instanceof Ext.dom.Shim).toBe(true);
            expect(mask.el.shim.el.isVisible()).toBe(true);

            Ext.useShims = false;
        });

        it("should hide the shim when the loadmask is hidden", function() {
            createMask({
                shim: true
            }).show();

            mask.hide();

            expect(mask.el.shim.el).toBeNull();
        });

        it("should show the shim when the loadmask is shown", function() {
            createMask({
                shim: true
            }).show();

            mask.hide();
            mask.show();

            expect(mask.el.shim instanceof Ext.dom.Shim).toBe(true);
            expect(mask.el.shim.el.isVisible()).toBe(true);
        });

        it("should allow shim to be enabled after first show", function() {
            createMask().show();

            expect(mask.el.shim).toBeUndefined();

            mask.hide();

            // similar to what Ext.Component#setLoading does - just sets the property
            mask.shim = true;

            mask.show();

            expect(mask.el.shim instanceof Ext.dom.Shim).toBe(true);
            expect(mask.el.shim.el.isVisible()).toBe(true);
        });

        it("should allow shim to be disabled after first show", function() {
            createMask({
                shim: true
            }).show();

            expect(mask.el.shim instanceof Ext.dom.Shim).toBe(true);
            expect(mask.el.shim.el.isVisible()).toBe(true);

            mask.hide();

            // similar to what Ext.Component#setLoading does - just sets the property
            mask.shim = false;

            mask.show();

            expect(mask.el.shim.el).toBeNull();
        });
    });

    describe("detached owner", function() {
        it("should not show", function() {
            createMask();

            target.detachFromBody();

            mask.loading = true;
            mask.maybeShow();

            expect(mask.isVisible()).toBe(false);
        });
    });

    describe("ARIA", function() {
        beforeEach(function() {
            createMask();
        });

        it("should have progressbar role", function() {
            expect(mask).toHaveAttr('role', 'progressbar');
        });

        it("should not have aria-valuemin attribute", function() {
            expect(mask).not.toHaveAttr('aria-valuemin');
        });

        it("should not have aria-valuemax attribute", function() {
            expect(mask).not.toHaveAttr('aria-valuemax');
        });

        it("should not have aria-valuenow attribute", function() {
            expect(mask).not.toHaveAttr('aria-valuenow');
        });

        it("should not have aria-valuetext by default", function() {
            expect(mask).not.toHaveAttr('aria-valuetext');
        });

        it("should have aria-valuetext after show", function() {
            mask.show();

            expect(mask).toHaveAttr('aria-valuetext', 'Loading...');
        });

        it("should remove aria-valuetext if useMsg is false", function() {
            mask.useMsg = false;
            mask.show();

            expect(mask).not.toHaveAttr('aria-valuetext');
        });
    });
});
