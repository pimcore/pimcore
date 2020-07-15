/* global expect, spyOn, Ext, jasmine, xdescribe, describe */

topSuite("Ext.util.Floating",
    ['Ext.window.Window', 'Ext.menu.Menu', 'Ext.Button', 'Ext.form.Panel', 'Ext.form.Label', 'Ext.form.field.*',
     'Ext.grid.Panel'],
function() {
    var component,
        describeGoodBrowsers = Ext.isWebKit || Ext.isGecko || Ext.isChrome ? describe : xdescribe,
        itNotTouch = jasmine.supportsTouch ? xit : it;

    function makeComponent(cfg) {
        component = new Ext.Component(Ext.apply({
            floating: true
        }, cfg));
    }

    function spyOnEvent(object, eventName, fn, options) {
        var obj = {
            fn: fn || Ext.emptyFn
        },
        spy = spyOn(obj, 'fn');

        object.addListener(eventName, obj.fn, null, options);

        return spy;
    }

    afterEach(function() {
        if (component) {
            component.destroy();
            component = null;
        }
    });

    it('should fire the deactivate event once on hide', function() {
        makeComponent();
        var activateSpy = spyOnEvent(component, 'activate'),
            deactivateSpy = spyOnEvent(component, 'deactivate');

        component.show();

        // That should have fired the activate event once
        expect(activateSpy.callCount).toBe(1);
        expect(deactivateSpy.callCount).toBe(0);

        component.hide();

        // That should have fired the deactivate event once
        expect(activateSpy.callCount).toBe(1);
        expect(deactivateSpy.callCount).toBe(1);
    });

    it("should call the floating constructor on first show", function() {
        makeComponent();

        spyOn(component.mixins.floating, 'constructor').andCallThrough();

        component.show();

        expect(component.mixins.floating.constructor).toHaveBeenCalled();
    });

    it("should have the x-layer CSS class on its element", function() {
        makeComponent();
        component.show();

        expect(component.el).toHaveCls('x-layer');
    });

    it("should have the x-fixed-layer CSS class if fixed is true", function() {
        makeComponent({
            fixed: true
        });
        component.show();

        expect(component.el).toHaveCls('x-fixed-layer');
    });

    it("should wait until first show to render the component", function() {
        makeComponent();
        expect(component.rendered).toBe(false);
        expect(component.el).toBeUndefined();

        component.show();

        expect(component.rendered).toBe(true);
        expect(component.el instanceof Ext.dom.Element).toBe(true);
    });

    it("should render the component to the renderTo element", function() {
        var el = Ext.getBody().createChild();

        makeComponent({
            renderTo: el
        });

        expect(component.rendered).toBe(true);
        expect(component.el.parent()).toBe(el);
        expect(component.el.isVisible()).toBe(true);
        el.destroy();
    });

    it("should render the component as hidden to the renderTo el if hidden is true", function() {
        var el = Ext.getBody().createChild();

        makeComponent({
            renderTo: el,
            hidden: true
        });

        expect(component.rendered).toBe(true);
        expect(component.el.parent()).toBe(el);
        expect(component.el.isVisible()).toBe(false);
        el.destroy();
    });

    it("it should show the element when the component is shown", function() {
        makeComponent();
        component.show();

        expect(component.el.isVisible()).toBe(true);
    });

    it("it should hide the element when the component is hidden", function() {
        makeComponent();
        component.show();
        component.hide();

        expect(component.el.isVisible()).toBe(false);
    });

    describe("shim", function() {
        it("should not have a shim by default", function() {
            makeComponent();
            component.show();

            expect(component.el.shim).toBeUndefined();
        });

        it("should create a shim if shim is true", function() {
            makeComponent({
                shim: true
            });
            component.show();

            expect(component.el.shim instanceof Ext.dom.Shim).toBe(true);
        });

        it("should create a shim if Ext.useShims is true", function() {
            Ext.useShims = true;
            makeComponent({
                shim: true
            });
            component.show();

            expect(component.el.shim instanceof Ext.dom.Shim).toBe(true);

            Ext.useShims = false;
        });

        it("should set position:fixed on the shim if fixed is true", function() {
            makeComponent({
                fixed: true,
                shim: true
            });
            component.show();

            expect(component.el.shim.el.getStyle('position')).toBe('fixed');
        });
    });

    describe("shadow", function() {
        it("should have a shadow by default", function() {
            makeComponent();
            component.show();

            expect(component.el.shadow instanceof Ext.dom.Shadow).toBe(true);
        });

        it("should not have a shadow if shadow is false", function() {
            makeComponent({
                shadow: false
            });
            component.show();

            expect(component.el.shadow).toBeUndefined();
        });

        it("should pass shadowOffset along to the shadow", function() {
            makeComponent({
                shadowOffset: 15
            });
            component.show();

            expect(component.el.shadow.offset).toBe(15);
        });

        it("should use 'sides' as the default mode", function() {
            makeComponent();
            component.show();

            expect(component.el.shadow.mode).toBe('sides');
        });

        it("should pass a string shadow config along as the 'mode' config of the shadow", function() {
            makeComponent({
                shadow: 'drop'
            });
            component.show();

            expect(component.el.shadow.mode).toBe('drop');
        });

        it("should set position:fixed on the shadow if fixed is true", function() {
            makeComponent({
                fixed: true
            });
            component.show();

            expect(component.el.shadow.el.getStyle('position')).toBe('fixed');
        });

        it("should hide the shadow during animations", function() {
            var animationDone = false,
                shadow, shadowEl, shadowHideSpy;

            makeComponent({
                width: 200,
                height: 100,
                x: 100,
                y: 100
            });
            component.show();

            shadow = component.el.shadow;
            shadowEl = shadow.el;

            expect(shadowEl.isVisible()).toBe(true);
            shadowHideSpy = spyOn(shadowEl, 'hide').andCallThrough();

            component.el.setXY([350, 400], {
                duration: 200,
                listeners: {
                    afteranimate: function() {
                        animationDone = true;
                    }
                }
            });

            waitsForSpy(shadowHideSpy, "shadow to be hidden for animation");

            waitsFor(function() {
                return animationDone && shadow.el && shadow.el.isVisible();
            }, 'shadow to be shown after animation finishes');

            runs(function() {

                // IE8 does shadows the hard way
                expect(shadow.el.getX()).toBe(Ext.isIE8 ? 345 : 350);
                expect(shadow.el.getY()).toBe(Ext.isIE8 ? 397 : 404);

                // FFWindows gets this off by one
                expect(shadow.el.getWidth()).toBeApprox(Ext.isIE8 ? 209 : 200, 1);
                expect(shadow.el.getHeight()).toBe(Ext.isIE8 ? 107 : 96);
            });
        });

        it("should not hide the shadow during animations if animateShadow is true", function() {
            var animationDone = false,
                shadow;

            makeComponent({
                animateShadow: {
                    duration: 100
                },
                width: 200,
                height: 100,
                x: 100,
                y: 100
            });
            component.show();

            shadow = component.el.shadow;

            spyOn(shadow, 'hide').andCallThrough();

            expect(shadow.el.isVisible()).toBe(true);

            component.el.setXY([350, 400], {
                duration: 50,
                listeners: {
                    afteranimate: function() {
                        animationDone = true;
                    }
                }
            });

            waitsFor(function() {
                return animationDone;
            }, "Animation never completed", 300);

            runs(function() {
                expect(shadow.hide).not.toHaveBeenCalled();
                expect(shadow.el.isVisible()).toBe(true);

                // IE8 does shadows the hard way
                expect(shadow.el.getX()).toBe(Ext.isIE8 ? 345 : 350);
                expect(shadow.el.getY()).toBe(Ext.isIE8 ? 397 : 404);
                expect(shadow.el.getWidth()).toBeApprox(Ext.isIE8 ? 209 : 200, 1);
                expect(shadow.el.getHeight()).toBe(Ext.isIE8 ? 107 : 96);
            });
        });
    });

    describe("onFocusTopmost", function() {
        describe("focus", function() {
            it("should not focus the floater if a descendant component contains focus", function() {
                component = new Ext.window.Window({
                    autoShow: true,
                    floating: true,
                    items: [{
                        xtype: 'textfield',
                        itemId: 'text'
                    }]
                });
                var text = component.down('#text');

                jasmine.focusAndWait(text);
                runs(function() {
                    component.onFocusTopmost();
                });
                jasmine.waitAWhile();
                runs(function() {
                    expect(Ext.ComponentManager.getActiveComponent()).toBe(text);
                });
            });

            it("should not focus the floater if a descendant component contains focus and it is not in the same DOM hierarchy", function() {
                component = new Ext.window.Window({
                    autoShow: true,
                    floating: true
                });

                var text = new Ext.form.field.Text({
                    renderTo: Ext.getBody(),
                    getRefOwner: function() {
                        return component;
                    }
                });

                jasmine.focusAndWait(text);
                runs(function() {
                    component.onFocusTopmost();
                });
                jasmine.waitAWhile();
                runs(function() {
                    expect(Ext.ComponentManager.getActiveComponent()).toBe(text);
                    text.destroy();
                });
            });
        });
    });

    describe("scroll alignment when rendered to body", function() {
        var spy, c, scroller, floater, count,
            oldOnError = window.onerror;

        function makeTestComponent(alignToComponent) {
            spy = jasmine.createSpy();

            count = Ext.GlobalEvents.hasListeners.scroll;

            c = {
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                scrollable: true
            };

            if (alignToComponent) {
                c.items = [{
                    xtype: 'component',
                    autoEl: {
                        html: 'A',
                        style: 'float:left;width:100px;height:500px'
                    }
                }, {
                    xtype: 'component',
                    id: 'align',
                    autoEl: {
                        html: 'B',
                        style: 'float:left;width:100px;height:200px'
                    }
                }];
            }
            else {
                c.html = Ext.DomHelper.createHtml({
                    children: [{
                        html: 'A',
                        style: {
                            'float': 'left',
                            width: '100px',
                            height: '500px'
                        }
                    }, {
                        html: 'B',
                        cls: 'align',
                        style: {
                            'float': 'left',
                            width: '100px',
                            height: '200px'
                        }
                    }]
                });
            }

            c = new (alignToComponent ? Ext.Container : Ext.Component)(c);
            scroller = c.getScrollable();
            scroller.refresh(true);

            floater = new Ext.Component({
                autoShow: true,
                floating: true,
                shadow: false,
                width: 50,
                height: 50,
                style: 'border: 1px solid black'
            });
        }

        afterEach(function() {
            Ext.un('scroll', spy);
            count = c = floater = spy = Ext.destroy(floater, c);
            window.onerror = oldOnError;
        });

        describe('aligning to element', function() {
            beforeEach(function() {
                makeTestComponent(false);
            });

            it("should keep the floater aligned on scroll", function() {
                floater.alignTo(c.getEl().down('.align', true), 'tl-bl');

                expect(floater.getEl().getTop()).toBe(200);

                Ext.on('scroll', spy);

                scroller.scrollTo(null, 50);

                waitsFor(function() {
                    return spy.callCount === 1;
                });

                runs(function() {
                    // Should realign on scroll event
                    expect(floater.getEl().getTop()).toBe(150);
                    scroller.scrollTo(null, 100);
                });

                waitsFor(function() {
                    return spy.callCount === 2;
                });

                runs(function() {
                    // Should realign on scroll event
                    expect(floater.getEl().getTop()).toBe(100);
                });
            });

            it("should unbind the scroll listener on destroy", function() {
                floater.alignTo(c.getEl().down('.align', true), 'tl-bl');
                floater.destroy();
                expect(Ext.GlobalEvents.hasListeners.scroll).toBe(count);
            });

            it("should not move the element if the alignTo element is destroyed", function() {
                floater.alignTo(c.getEl().down('.align'), 'tl-bl');

                expect(floater.getEl().getTop()).toBe(200);

                c.getEl().down('.align').destroy();

                Ext.on('scroll', spy);

                runs(function() {
                    scroller.scrollTo(null, 100);
                });

                waitsFor(function() {
                    return spy.callCount === 1;
                });

                runs(function() {
                    expect(floater.getEl().getTop()).toBe(200);
                });
            });

            it('should unbind the resize listener when alignTo element is destroyed', function() {
                var alignEl = c.getEl().down('.align', true),
                    spy = spyOnEvent(Ext.GlobalEvents, 'resize', null, {
                        buffer: 200
                    }),
                    onErrorSpy = jasmine.createSpy();

                floater.alignTo(alignEl, 'tl-bl');

                expect(floater.getEl().getTop()).toBe(200);

                alignEl.parentNode.removeChild(alignEl);

                window.onerror = onErrorSpy.andCallFake(function() {
                    if (oldOnError) {
                        oldOnError();
                    }
                });

                Ext.GlobalEvents.fireEvent('resize', 500, 500);

                waitsFor(function() {
                    return spy.callCount === 1;
                });

                runs(function() {
                    Ext.GlobalEvents.fireEvent('resize', 1000, 1000);
                });

                waitsFor(function() {
                    return spy.callCount === 2;
                });

                runs(function() {
                    expect(onErrorSpy).not.toHaveBeenCalled();
                    Ext.GlobalEvents.un('resize', spy);
                });
            });
        });

        describe('aligning to component', function() {
            beforeEach(function() {
                makeTestComponent(true);
            });

            it("should keep the floater aligned on scroll", function() {
                floater.alignTo(c.down('#align'), 'tl-bl');

                expect(floater.getEl().getTop()).toBe(200);

                Ext.on('scroll', spy);

                scroller.scrollTo(null, 50);

                waitsFor(function() {
                    return spy.callCount === 1;
                });

                runs(function() {
                    // Should realign on scroll event
                    expect(floater.getEl().getTop()).toBe(150);
                    scroller.scrollTo(null, 100);
                });

                waitsFor(function() {
                    return spy.callCount === 2;
                });

                runs(function() {
                    // Should realign on scroll event
                    expect(floater.getEl().getTop()).toBe(100);
                    c.down('#align').destroy();
                });
            });

            it("should unbind the scroll listener on destroy", function() {
                floater.alignTo(c.down('#align'), 'tl-bl');
                floater.destroy();
                expect(Ext.GlobalEvents.hasListeners.scroll).toBe(count);
            });

            it("should not move the element if the alignTo element is destroyed", function() {
                floater.alignTo(c.down('#align'), 'tl-bl');

                expect(floater.getEl().getTop()).toBe(200);

                c.down('#align').destroy();

                Ext.on('scroll', spy);

                runs(function() {
                    scroller.scrollTo(null, 100);
                });

                waitsFor(function() {
                    return spy.callCount === 1;
                });

                runs(function() {
                    expect(floater.getEl().getTop()).toBe(200);
                });
            });
        });
    });

    describe("scroll alignment when rendered into the scrolling element", function() {
        var spy, c, scroller, floater, count;

        function makeTestComponent(alignToComponent) {
            spy = jasmine.createSpy();

            count = Ext.GlobalEvents.hasListeners.scroll;

            c = {
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                scrollable: true
            };

            if (alignToComponent) {
                c.items = [{
                    xtype: 'component',
                    autoEl: {
                        html: 'A',
                        style: 'float:left;width:100px;height:500px'
                    }
                }, {
                    xtype: 'component',
                    id: 'align',
                    autoEl: {
                        html: 'B',
                        style: 'float:left;width:100px;height:200px'
                    }
                }];
            }
            else {
                c.html = Ext.DomHelper.createHtml({
                    children: [{
                        html: 'A',
                        style: {
                            'float': 'left',
                            width: '100px',
                            height: '500px'
                        }
                    }, {
                        html: 'B',
                        cls: 'align',
                        style: {
                            'float': 'left',
                            width: '100px',
                            height: '200px'
                        }
                    }]
                });
            }

            c = new (alignToComponent ? Ext.Container : Ext.Component)(c);
            scroller = c.getScrollable();
            scroller.refresh(true);

            // Render the floater into the scrolling element
            floater = new Ext.Component({
                autoShow: true,
                floating: true,
                shadow: false,
                width: 50,
                height: 50,
                style: 'border: 1px solid black',
                renderTo: c.getContentTarget()
            });
        }

        afterEach(function() {
            Ext.un('scroll', spy);
            count = c = floater = spy = Ext.destroy(floater, c);
        });

        describe('aligning to Element', function() {
            beforeEach(function() {
                makeTestComponent(false);
            });

            it("should keep the floater aligned on scroll", function() {
                var alignToSpy = spyOn(floater.mixins.positionable, 'alignTo').andCallThrough();

                floater.alignTo(c.getEl().down('.align'), 'tl-bl');

                // We've called it once, so it will align.
                // From now on, when we scroll, it should NOT call align
                // because it is rendered within the scrolling element.
                expect(alignToSpy.callCount).toBe(1);

                expect(floater.getEl().getTop()).toBe(200);

                Ext.on('scroll', spy);

                scroller.scrollTo(null, 50);

                // We expect nothing to happen on scroll so we cannot wait for anything.
                // The floater is rendered into the element which scrolls, so
                // it must not realign - it will scroll along naturally.
                waits(100);

                runs(function() {
                    // Should NOT realign because it is scrolling with content
                    expect(alignToSpy.callCount).toBe(1);

                    expect(floater.getEl().getTop()).toBe(150);
                    scroller.scrollTo(null, 100);
                });

                // We expect nothing to happen on scroll so we cannot wait for anything.
                // The floater is rendered into the element which scrolls, so
                // it must not realign - it will scroll along naturally.
                waits(100);

                runs(function() {
                    // Should NOT realign because it is scrolling with content
                    expect(alignToSpy.callCount).toBe(1);

                    expect(floater.getEl().getTop()).toBe(100);

                    c.getEl().down('.align').destroy();
                });
            });
        });

        describe('aligning to Component', function() {
            beforeEach(function() {
                makeTestComponent(true);
            });

            it("should keep the floater aligned on scroll", function() {
                var alignToSpy = spyOn(floater.mixins.positionable, 'alignTo').andCallThrough();

                floater.alignTo(c.down('#align'), 'tl-bl');

                // We've called it once, so it will align.
                // From now on, when we scroll, it should NOT call align
                // because it is rendered within the scrolling element.
                expect(alignToSpy.callCount).toBe(1);

                expect(floater.getEl().getTop()).toBe(200);

                Ext.on('scroll', spy);

                scroller.scrollTo(null, 50);

                // We expect nothing to happen on scroll so we cannot wait for anything.
                // The floater is rendered into the element which scrolls, so
                // it must not realign - it will scroll along naturally.
                waits(100);

                runs(function() {
                    // Should NOT realign because it is scrolling with content
                    expect(alignToSpy.callCount).toBe(1);

                    expect(floater.getEl().getTop()).toBe(150);
                    scroller.scrollTo(null, 100);
                });

                // We expect nothing to happen on scroll so we cannot wait for anything.
                // The floater is rendered into the element which scrolls, so
                // it must not realign - it will scroll along naturally.
                waits(100);

                runs(function() {
                    // Should NOT realign because it is scrolling with content
                    expect(alignToSpy.callCount).toBe(1);

                    expect(floater.getEl().getTop()).toBe(100);
                });
            });
        });
    });

    describeGoodBrowsers('Chained aligning and scrolling and clipping', function() {
        var panel;

        beforeEach(function() {
            // We test clipping behaviour
            Ext.menu.Menu.prototype.alignOnScroll = true;

            var items = [];

            for (var i = 0; i < 10; i++) {
                items.push({
                    xtype: 'combobox',
                    itemId: 'combo' + (i + 1),
                    fieldLabel: 'ComboBox' + (i + 1),
                    store: [
                        'Foo',
                        'Bar',
                        'Bletch',
                        'Ik',
                        'Screeble',
                        'Raz',
                        'Poot',
                        'Honk',
                        'Flap',
                        'Gibber',
                        'Tweet'
                    ]
                }, {
                    xtype: 'grid',
                    itemId: 'grid' + (i + 1),
                    title: 'Small Grid',
                    frame: true,
                    width: 220,
                    style: 'margin:0 0 5px 100px',
                    columns: [{
                        text: 'Col 1',
                        dataIndex: 'col1'
                    }, {
                        text: 'Col 2',
                        dataIndex: 'col2'
                    }],
                    store: {
                        fields: ['col1', 'col2'],
                        data: [
                            { col1: 'grid' + (i + 1) + '/1', col2: 'grid' + (i + 1) + '/2' }
                        ]
                    }
                }, {
                    xtype: 'button',
                    itemId: 'button' + (i + 1),
                    text: 'Button',
                    style: 'margin:0 0 5px 100px',
                    menu: [{
                        text: 'Button Menu 1'
                    }, {
                        text: 'Button Menu 2'
                    }]
                });
            }

            panel = new Ext.form.Panel({
                frame: true,
                style: 'marginTop:50px',
                scrollable: true,
                title: 'Lots of Combos',
                height: 400, width: 600,
                renderTo: document.body,
                items: items
            });
        });
        afterEach(function() {
            // We test clipping behaviour
            Ext.menu.Menu.prototype.alignOnScroll = false;
            panel.destroy();
        });

        itNotTouch('should clip at the top when scrolling a floater outside the top boundary', function() {
            var grid1 = panel.down('#grid1'),
                col = grid1.down('gridcolumn'),
                headerMenu,
                columnsItem,
                columnsMenu,
                headerMenuY,
                scrolledHeaderMenuY,
                columnsMenuY,
                scrolledColumnsMenuY;

            jasmine.fireMouseEvent(col, 'mouseover');
            jasmine.fireMouseEvent(col.triggerEl, 'click');
            headerMenu = col.activeMenu;
            columnsItem = headerMenu.child('[text=Columns]');
            jasmine.fireMouseEvent(columnsItem.el, 'mouseover');

            waitsFor(function() {
                columnsMenu = columnsItem.menu;

                return columnsMenu && columnsMenu.isVisible();
            });
            runs(function() {
                headerMenuY = headerMenu.getY();
                columnsMenuY = columnsMenu.getY();
                panel.scrollBy(0, 100);
            });
            waitsFor(function() {
                return !!headerMenu.el.dom.style.clip;
            });
            runs(function() {
                scrolledHeaderMenuY = headerMenu.getY();
                scrolledColumnsMenuY = columnsMenu.getY();
                var overflow = Math.max(0, panel.body.getY() - scrolledHeaderMenuY);

                // Menus should BOTH have bumped upwards by exactly the amount we scrolled
                expect(scrolledHeaderMenuY).toBe(headerMenuY - 100);
                expect(scrolledColumnsMenuY).toBe(columnsMenuY - 100);

                // And the overflowing top of it shuold have been clipped off.
                // Note that some browsers return comma separated values for the clip rect.
                expect(Ext.String.startsWith(headerMenu.el.dom.style.clip, 'rect(' + overflow + 'px')).toBe(true);
            });
        });

        itNotTouch('should not clip at the bottom when floater extends outside the bottom boundary and anchor is fully visible', function() {
            var grid9 = panel.down('#grid9'),
                col = grid9.down('gridcolumn'),
                headerMenu,
                columnsItem,
                columnsMenu;

            panel.getScrollable().ensureVisible(grid9.el);

            jasmine.fireMouseEvent(col, 'mouseover');
            jasmine.fireMouseEvent(col.triggerEl, 'click');
            headerMenu = col.activeMenu;
            columnsItem = headerMenu.child('[text=Columns]');
            jasmine.fireMouseEvent(columnsItem.el, 'mouseover');

            waitsFor(function() {
                columnsMenu = columnsItem.menu;

                return columnsMenu && columnsMenu.isVisible();
            });
            runs(function() {
                // No clipping
                expect(columnsMenu.el.dom.style.clip).toBe('');
            });
        });

        // If the flaoters overflow the scroll area, but we've reached the scroll end, and there's not enough scroll left
        // to bring them into view, then the floaters must be made available.
        itNotTouch("should clip at the bottom when scrolling a floater's anchor outside the bottom boundary", function() {
            var grid10 = panel.down('#grid10'),
                col = grid10.down('gridcolumn'),
                headerMenu,
                columnsItem,
                columnsMenu,
                headerMenuY,
                scrolledHeaderMenuY,
                columnsMenuY,
                scrolledColumnsMenuY;

            panel.getScrollable().ensureVisible(grid10.el);

            jasmine.fireMouseEvent(col, 'mouseover');
            jasmine.fireMouseEvent(col.triggerEl, 'click');
            headerMenu = col.activeMenu;
            columnsItem = headerMenu.child('[text=Columns]');
            jasmine.fireMouseEvent(columnsItem.el, 'mouseover');

            waitsFor(function() {
                columnsMenu = columnsItem.menu;

                return columnsMenu && columnsMenu.isVisible();
            });
            runs(function() {
                // Header menu still visible because its anchor element is within the view
                expect(headerMenu.el.dom.style.clip).toBe('');

                // Columns menu overflows the bottom but it is NOT cliped because
                // it cannot be scrolled into view
                expect(columnsMenu.el.dom.style.clip).toBe('');

                headerMenuY = headerMenu.getY();
                columnsMenuY = columnsMenu.getY();
                panel.scrollBy(0, -20);
            });
            waitsFor(function() {
                return headerMenu.getY() === headerMenuY + 20 && columnsMenu.getY() === columnsMenuY + 20;
            });
            runs(function() {
                scrolledHeaderMenuY = headerMenu.getY();
                scrolledColumnsMenuY = columnsMenu.getY();

                // Menus should BOTH have bumped upwards by exactly the amount we scrolled
                expect(scrolledHeaderMenuY).toBe(headerMenuY + 20);
                expect(scrolledColumnsMenuY).toBe(columnsMenuY + 20);

                // Must not have been clipped because we're at the bottom of the scroll
                expect(headerMenu.el.dom.style.clip).toBe('');
                panel.scrollBy(0, -20);
            });
            waitsFor(function() {
                return headerMenu.getY() === scrolledHeaderMenuY + 20 && columnsMenu.getY() === scrolledColumnsMenuY + 20;
            });
            runs(function() {
                // The header trigger ell is clipped, so both menus should be clipped out of visibility.
                // Note that some browsers return comma separated values for the clip rect.
                expect(headerMenu.el.dom.style.clip.replace(/,\s*/g, ' ')).toBe("rect(-10000px 10000px 0px -10000px)");
                expect(headerMenu.el.dom.style.clip.replace(/,\s*/g, ' ')).toBe("rect(-10000px 10000px 0px -10000px)");
            });
        });
    });

    describe('showing a focusable floater while there is an unfocable, alwaysOnTop floater visible', function() {
        var transientCmp, focusable;

        afterEach(function() {
            Ext.destroy(transientCmp, focusable);
        });
        it('should focus the focusable', function() {
            transientCmp = new Ext.Component({
                focusable: false,
                floating: true,
                alwaysOnTop: true,
                renderTo: document.body
            });

            focusable = new Ext.window.Window({
                title: 'I should get focused'
            }).show();

            // It's not the topmost in the stack, but its the topmost focusable
            // so it must get focused.
            waitsFor(function() {
                return focusable.containsFocus;
            });
        });
    });

    describe('showing an already visible floater', function() {
        var w, w1;

        afterEach(function() {
            Ext.destroy(w, w1);
        });

        it('should move to front if shown when already visible', function() {
            w = new Ext.window.Window({
                title: 'Bar',
                width: 200,
                height: 200,
                autoShow: true
            });

            w1 = new Ext.window.Window({
                title: 'Foo',
                width: 100,
                height: 100,
                autoShow: true
            });

            w.toFront();

            // Window w should be on top.
            expect(w.el.getZIndex()).toBeGreaterThan(w1.el.getZIndex());

            w1.show();

            // Window w1 should now be on top, even though it was already visible.
            expect(w1.el.getZIndex()).toBeGreaterThan(w.el.getZIndex());
        });
    });

    describe('window rendering', function() {
        var w,
        button,
        onElementShowSpy;

        afterEach(function() {
            Ext.destroy(w, button);
        });

        it('should not render shadow to destination if there is animation', function() {

            button = Ext.create('Ext.Button', {
                text: 'Click me',
                renderTo: Ext.getBody(),
                handler: function() {
                    w = Ext.create('Ext.window.Window', {
                        autoShow: true,
                        viewModel: {
                            data: {
                                demo: 'Some Text Here'
                            }
                        },
                        animateTarget: button,
                        height: 100,
                        width: 430,
                        items: [{
                            xtype: 'label',
                            bind: {
                                text: '{demo}'
                            }
                        }]

                    });

                    onElementShowSpy = spyOn(w.el, 'show').andCallFake(function(anim) {
                        expect(!w.el.shadow.hidden).toBe(!(w.el.getData().isVisible === false));
                    });
                }
            });
            button.click();
            waitsFor(function() {
                return onElementShowSpy.callCount === 1;
            }, "Element not attempted to be visible", 1000);
        });

        it('should render shadow to destination if there is no animation', function() {
            button = Ext.create('Ext.Button', {
                text: 'Click me',
                renderTo: Ext.getBody(),
                handler: function() {
                    w = Ext.create('Ext.window.Window', {
                        autoShow: true,
                        viewModel: {
                            data: {
                                demo: 'Some Text Here'
                            }
                        },
                        height: 100,
                        width: 430,
                        items: [{
                            xtype: 'label',
                            bind: {
                                text: '{demo}'
                            }
                        }]

                    });
                }
            });
            button.click();
            expect(!w.el.shadow.hidden).toBe(!(w.el.getData().isVisible === false));
        });
    });
});
