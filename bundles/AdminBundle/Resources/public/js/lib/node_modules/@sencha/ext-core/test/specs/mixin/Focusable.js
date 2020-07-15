// Focusable mixin lives in the core but is tested in the context of either
// Modern or Classic component
topSuite("Ext.mixin.Focusable",
    Ext.isModern
        ? ['Ext.Panel', 'Ext.Button', 'Ext.form.Text']
        : ['Ext.Panel', 'Ext.Button', 'Ext.form.Text', 'Ext.form.FieldSet'],
function() {
    var isModern = Ext.toolkit === 'modern',
        c, container;

    function makeSuite(type) {
        function stdComponent(config) {
            var cmpCfg = Ext.apply({
                extend: 'Ext.' + type,
                width: 100,
                height: 100,
                style: {
                    'background-color': 'green'
                },
                focusable: true,
                tabIndex: 0
            }, config);

            if (isModern) {
                cmpCfg.element = cmpCfg.element || {
                    reference: 'element',
                    tag: cmpCfg.autoEl || 'div'
                };

                Ext.applyIf(cmpCfg.element, {
                    listeners: {
                        focus: 'handleFocusEvent',
                        blur: 'handleBlurEvent'
                    }
                });

                delete cmpCfg.autoEl;
            }

            return cmpCfg;
        }

        function childComponent(config) {
            var cmpCfg, Component;

            cmpCfg = stdComponent(config);
            Component = Ext.define(null, cmpCfg);

            return new Component();
        }

        function makeComponent(config) {
            var renderTo = Ext.getBody(),
                html;

            if (config) {
                html = config.html;

                if ('renderTo' in config && config.renderTo === undefined) {
                    renderTo = null;
                }

                delete config.renderTo;
            }

            c = childComponent(config);

            if (renderTo) {
                c.render(renderTo);
            }

            if (c.self.superclass.$className === 'Ext.Widget' && html) {
                c.element.dom.innerHTML = html;
            }

            return c;
        }

        function makeContainer(config) {
            container = new Ext.Container(Ext.apply({
                width: 100,
                height: 200,
                renderTo: Ext.getBody()
            }, config));

            // Modern needs this nudge because focusable is not a config
            // and is not set on the instance early enough. This should
            // only happen in unit tests where focusable property might
            // be passed via instance config.
            if (isModern && config && config.focusable) {
                container.initFocusableEvents(true);
            }

            return container;
        }

        afterEach(function() {
            if (container) {
                container.destroy();
            }

            if (c) {
                c.destroy();
            }

            c = container = null;
        });

        describe(type, function() {
            describe("tabIndex handling", function() {
                describe("component not focusable", function() {
                    it("should not render tabindex attribute when tabIndex property is undefined", function() {
                        makeComponent({
                            focusable: undefined,
                            tabIndex: undefined
                        });

                        expect(c).not.toHaveAttr('tabIndex');
                    });

                    it("should not render tabindex attribute when tabIndex property is defined", function() {
                        makeComponent({
                            focusable: undefined,
                            tabIndex: 0
                        });

                        expect(c).not.toHaveAttr('tabIndex');
                    });
                });

                describe("component is focusable", function() {
                    it("should not render tabindex attribute when tabIndex property is undefined", function() {
                        makeComponent({
                            focusable: true,
                            tabIndex: undefined
                        });

                        expect(c).not.toHaveAttr('tabIndex');
                    });

                    it("should render tabindex attribute when tabIndex property is defined", function() {
                        makeComponent({
                            focusable: true,
                            tabIndex: 0
                        });

                        expect(c).toHaveAttr('tabIndex', '0');
                    });
                });
            });

            describe("isFocusable", function() {
                describe("component", function() {
                    describe("not rendered", function() {
                        beforeEach(function() {
                            makeComponent({
                                renderTo: undefined
                            });
                        });

                        it("should return false", function() {
                            expect(c.isFocusable()).toBe(false);
                        });
                    });

                    describe("rendered", function() {
                        beforeEach(function() {
                            makeComponent();
                        });

                        describe("focusable === true", function() {
                            it("should return true when visible", function() {
                                expect(c.isFocusable()).toBe(true);
                            });

                            it("should return false when disabled", function() {
                                c.disable();

                                expect(c.isFocusable()).toBe(false);
                            });

                            it("should return false when invisible", function() {
                                c.hide();

                                expect(c.isFocusable()).toBe(false);
                            });
                        });

                        describe("focusable === false", function() {
                            beforeEach(function() {
                                c.focusable = false;
                            });

                            it("should return false", function() {
                                expect(c.isFocusable()).toBe(false);
                            });

                            it("should disregard deep parameter", function() {
                                spyOn(c, 'getFocusEl').andCallThrough();

                                expect(c.isFocusable(true)).toBe(false);
                                expect(c.getFocusEl).not.toHaveBeenCalled();
                            });
                        });
                    });
                });

                describe("container", function() {
                    describe("not rendered", function() {
                        beforeEach(function() {
                            makeContainer({
                                renderTo: undefined,
                                items: [
                                    childComponent()
                                ]
                            });
                        });

                        it("should return false with deep === false", function() {
                            expect(container.isFocusable()).toBe(false);
                        });

                        it("should return false with deep === true", function() {
                            expect(container.isFocusable(true)).toBe(false);
                        });
                    });

                    describe("rendered", function() {
                        var fooCmp;

                        beforeEach(function() {
                            makeContainer({
                                items: [
                                    childComponent({
                                        itemId: 'foo'
                                    })
                                ],

                                focusable: true,
                                tabIndex: 0
                            });

                            fooCmp = container.down('#foo');
                        });

                        describe("deep === false", function() {
                            describe("focusable === false", function() {
                                beforeEach(function() {
                                    container.focusable = false;
                                });

                                it("should return false", function() {
                                    expect(container.isFocusable()).toBe(false);
                                });
                            });

                            describe("focusable === true, no tabIndex", function() {
                                beforeEach(function() {
                                    container.setTabIndex(undefined);
                                });

                                it("should return false", function() {
                                    expect(container.isFocusable()).toBe(false);
                                });
                            });

                            describe("focusable === true, tabIndex === 0", function() {
                                it("should return true when container is visible", function() {
                                    expect(container.isFocusable()).toBe(true);
                                });

                                it("should return false when container is hidden", function() {
                                    container.hide();

                                    expect(container.isFocusable()).toBe(false);
                                });

                                it("should return false when container is disabled", function() {
                                    container.disable();

                                    expect(container.isFocusable()).toBe(false);
                                });
                            });
                        });

                        describe("deep === true", function() {
                            beforeEach(function() {
                                container.setDefaultFocus('#foo');
                            });

                            describe("container not focusable", function() {
                                beforeEach(function() {
                                    container.focusable = false;
                                });

                                it("should return true when delegate is focusable", function() {
                                    expect(container.isFocusable(true)).toBe(true);
                                });

                                it("should return false when delegate is not focusable", function() {
                                    fooCmp.focusable = false;

                                    expect(container.isFocusable(true)).toBe(false);
                                });

                                it("should return false when delegate is hidden", function() {
                                    fooCmp.hide();

                                    expect(container.isFocusable(true)).toBe(false);
                                });

                                it("should return false when delegate is disabled", function() {
                                    fooCmp.disable();

                                    expect(container.isFocusable(true)).toBe(false);
                                });

                                it("should return false when delegate is destroyed", function() {
                                    fooCmp.destroy();

                                    expect(container.isFocusable(true)).toBe(false);
                                });

                                describe("dynamic delegate", function() {
                                    beforeEach(function() {
                                        container.remove(fooCmp);
                                        fooCmp.destroy();
                                        fooCmp = null;
                                    });

                                    it("should return false when delegate is removed", function() {
                                        expect(container.isFocusable(true)).toBe(false);
                                    });

                                    (isModern ? xit : it)("should return true when matching delegate is added", function() {
                                        container.add(makeComponent({
                                            itemId: 'foo'
                                        }, true));

                                        fooCmp = container.down('#foo');

                                        expect(container.isFocusable(true)).toBe(true);
                                    });
                                });
                            });

                            describe("container is focusable", function() {
                                it("should return true", function() {
                                    expect(container.isFocusable(true)).toBe(true);
                                });
                            });
                        });
                    });
                });
            });

            describe("getTabIndex", function() {
                beforeEach(function() {
                    makeComponent({
                        renderTo: undefined,
                        tabIndex: 42
                    });
                });

                it("should return undefined when !focusable", function() {
                    c.focusable = false;

                    expect(c.getTabIndex()).toBe(undefined);
                });

                it("should return configured tabIndex when component is not rendered", function() {
                    // In Classic, Widgets are considered "always rendered"
                    if (!isModern && type !== 'Widget') {
                        expect(c.rendered).toBeFalsy();
                    }

                    expect(c.getTabIndex()).toBe(42);
                });

                it("should return actual tabIndex when component is rendered", function() {
                    c.render(Ext.getBody());
                    c.el.set({ tabIndex: 1 });

                    expect(c.rendered).toBe(true);
                    expect(c.getTabIndex()).toBe(1);
                });
            });

            describe("setTabIndex", function() {
                beforeEach(function() {
                    makeComponent({
                        renderTo: undefined,
                        tabIndex: 43
                    });
                });

                it("should do nothing when !focusable", function() {
                    c.focusable = false;

                    c.setTabIndex(-1);

                    expect(c.tabIndex).toBe(43);
                });

                it("should set tabIndex property when not rendered", function() {
                    c.setTabIndex(-1);

                    expect(c.tabIndex).toBe(-1);
                });

                it("should set tabIndex property when el is a string", function() {
                    c.el = 'foo'; // element id

                    c.setTabIndex(-1);

                    expect(c.tabIndex).toBe(-1);
                });

                it("should set el tabindex when rendered", function() {
                    c.render(Ext.getBody());

                    c.setTabIndex(-1);

                    var index = c.el.getAttribute('tabIndex') - 0;

                    expect(index).toBe(-1);
                });
            });

            describe("container delegated getTabIndex/setTabIndex", function() {
                beforeEach(function() {
                    makeContainer({
                        focusable: true,

                        items: [childComponent({
                            tabIndex: 1
                        })]
                    });

                    container.getFocusEl = function() {
                        return this.child();
                    };

                    c = container.down('[tabIndex=1]');
                });

                it("should return child's tabIndex", function() {
                    expect(container.getTabIndex()).toBe(1);
                });

                it("should set child's tabIndex", function() {
                    container.setTabIndex(88);

                    var index = c.el.getAttribute('tabIndex') - 0;

                    expect(index).toBe(88);
                });
            });

            describe("focusCls handling", function() {
                var focusCls = Ext[type].prototype.focusCls;

                if (isModern || type === 'Widget') {
                    it("should default to 'x-focused'", function() {
                        expect(focusCls).toBe('x-focused');
                    });
                }
                else {
                    focusCls = 'x-' + focusCls;

                    it("should default to 'x-focus'", function() {
                        expect(focusCls).toBe('x-focus');
                    });
                }

                describe("focusClsEl === focusEl (default)", function() {
                    beforeEach(function() {
                        makeComponent();

                        focusAndWait(c);
                    });

                    describe("focusing", function() {
                        it("should add focusCls to el", function() {
                            expect(c.el.hasCls(focusCls)).toBe(true);
                        });
                    });

                    describe("blurring", function() {
                        beforeEach(function() {
                            c.blur();

                            waitAWhile();
                        });

                        it("should remove focusCls from el", function() {
                            expect(c.el.hasCls(focusCls)).toBe(false);
                        });
                    });

                    describe("disabling", function() {
                        beforeEach(function() {
                            // Disabling is synchronous, so no wait is necessary
                            c.disable();
                        });

                        it("should remove focusCls from el", function() {
                            expect(c.el.hasCls(focusCls)).toBe(false);
                        });
                    });
                });

                describe("focusClsEl is a child of el", function() {
                    beforeEach(function() {
                        makeComponent({
                            html: '<div class="focusClsEl">focusClsEl</div>'
                        });

                        c.focusClsEl = new Ext.dom.Fly();
                        c.focusClsEl.attach(c.el.down('.focusClsEl', true));

                        c.getFocusClsEl = function() {
                            return this.focusClsEl;
                        };

                        focusAndWait(c);
                    });

                    describe("focusing", function() {
                        it("should add focusCls to focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(true);
                        });

                        it("should not add focusCls to el", function() {
                            expect(c.el.hasCls(focusCls)).toBe(false);
                        });
                    });

                    describe("blurring", function() {
                        beforeEach(function() {
                            c.blur();

                            waitAWhile();
                        });

                        it("should remove focusCls from focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(false);
                        });
                    });

                    describe("disabling", function() {
                        beforeEach(function() {
                            // Disabling is synchronous, so no wait necessary
                            c.disable();
                        });

                        it("should remove focusCls from focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(false);
                        });
                    });
                });

                describe("focusClsEl is outside of el", function() {
                    beforeEach(function() {
                        makeComponent();

                        c.focusClsEl = Ext.getBody().createChild(
                            '<div>focusClsEl</div>'
                        );

                        c.getFocusClsEl = function() {
                            return this.focusClsEl;
                        };

                        focusAndWait(c);
                    });

                    afterEach(function() {
                        c.focusClsEl.destroy();
                        c.focusClsEl = null;
                    });

                    describe("focusing", function() {
                        it("should add focusCls to focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(true);
                        });

                        it("should not add focusCls to el", function() {
                            expect(c.el.hasCls(focusCls)).toBe(false);
                        });
                    });

                    describe("blurring", function() {
                        beforeEach(function() {
                            c.blur();

                            waitAWhile();
                        });

                        it("should remove focusCls from focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(false);
                        });
                    });

                    describe("disabling", function() {
                        beforeEach(function() {
                            // Disabling is synchronous, so no wait necessary
                            c.disable();
                        });

                        it("should remove focusCls from focusClsEl", function() {
                            expect(c.focusClsEl.hasCls(focusCls)).toBe(false);
                        });
                    });
                });
            });

            describe("blur/focus", function() {
                beforeEach(function() {
                    makeComponent({
                        autoEl: 'button'
                    });
                });

                it("should look up focused Component", function() {
                    focusAndWait(c);

                    runs(function() {
                        var cmp = Ext.ComponentManager.getActiveComponent();

                        expect(cmp).toEqual(c);
                    });
                });

                // Widgets and Modern Components do not support delayed focus
                if (!isModern && type !== 'Widget') {
                    it("should cancel previous delayed focus", function() {
                        var c2 = new Ext.Component({
                            autoEl: 'button',
                            focusable: true,
                            getFocusEl: function() {
                                return this.el;
                            }
                        });

                        spyOn(Ext.focusTask, 'delay').andCallThrough();
                        spyOn(Ext.focusTask, 'cancel').andCallThrough();

                        c.focus(false, true);

                        expect(Ext.focusTask.delay).toHaveBeenCalled();

                        c2.focus();

                        expect(Ext.focusTask.cancel).toHaveBeenCalled();

                        Ext.destroy(c2);
                    });
                }

                describe("focus delegation", function() {
                    var fooCmp, barCmp;

                    beforeEach(function() {
                        makeContainer({
                            height: 200,

                            items: [
                                childComponent({
                                    itemId: 'foo'
                                }),

                                childComponent({
                                    itemId: 'bar'
                                })
                            ]
                        });

                        fooCmp = container.down('#foo');
                        barCmp = container.down('#bar');
                    });

                    it("should focus foo", function() {
                        container.setDefaultFocus(type.toLowerCase());

                        // We're calling container.focus() here but expecting
                        // fooCmp to be focused
                        focusAndWait(container, fooCmp);

                        expectFocused(fooCmp);
                    });

                    it("should focus bar", function() {
                        container.setDefaultFocus('#bar');

                        focusAndWait(container, barCmp);

                        expectFocused(barCmp);
                    });
                });

                describe("events", function() {
                    var focusSpy, blurSpy;

                    beforeEach(function() {
                        focusSpy = jasmine.createSpy('focus');
                        blurSpy = jasmine.createSpy('blur');

                        c.on('focus', focusSpy);
                        c.on('blur', blurSpy);
                    });

                    afterEach(function() {
                        c.un('focus', focusSpy);
                        c.un('blur', blurSpy);

                        focusSpy = blurSpy = null;
                    });

                    describe("focus", function() {
                        beforeEach(function() {
                            c.focus();
                        });

                        it("should fire the focus event", function() {
                            waitForSpy(focusSpy);

                            runs(function() {
                                expect(focusSpy).toHaveBeenCalled();
                            });
                        });

                        it("should not fire the focus event if the component has focus", function() {
                            runs(function() {
                                c.focus();
                            });

                            waitForSpy(focusSpy);

                            // Enough time for the second event to fire, if any
                            waits(50);

                            runs(function() {
                                expect(focusSpy.callCount).toBe(1);
                            });
                        });
                    });

                    describe("blur", function() {
                        var beforeSpy;

                        beforeEach(function() {
                            beforeSpy = c.beforeBlur = jasmine.createSpy('beforeBlur');
                            c.focus();
                        });

                        it("should fire the blur event", function() {
                            runs(function() {
                                c.blur();
                            });

                            waitForSpy(blurSpy);

                            runs(function() {
                                expect(blurSpy).toHaveBeenCalled();
                            });
                        });

                        it("should not fire the blur event when component is blurred", function() {
                            runs(function() {
                                c.blur();
                            });

                            waitForSpy(blurSpy);

                            runs(function() {
                                blurSpy.reset();
                                c.blur();
                            });

                            waitForSpy(beforeSpy);

                            runs(function() {
                                expect(blurSpy).not.toHaveBeenCalled();
                            });
                        });

                        it("should set hasFocus to false before running beforeBlur", function() {
                            var hasFocus;

                            runs(function() {
                                beforeSpy.andCallFake(function() {
                                    hasFocus = this.hasFocus;
                                });

                                c.blur();
                            });

                            waitForSpy(blurSpy);

                            runs(function() {
                                expect(hasFocus).toBe(false);
                            });
                        });
                    });
                });
            });

            describe("enable/disable tabbing", function() {
                describe("simple component", function() {
                    beforeEach(function() {
                        makeComponent();

                        c.disableTabbing();
                    });

                    it("should disable tabbing", function() {
                        expect(c.el.isTabbable()).toBe(false);
                    });

                    it("should re-enable tabbing", function() {
                        c.enableTabbing();

                        expect(c.el.isTabbable()).toBe(true);
                    });
                });

                describe("non-focusable container with delegate", function() {
                    var delegate;

                    beforeEach(function() {
                        makeContainer({
                            defaultFocus: 'foo',
                            items: [
                                childComponent({ itemId: 'foo' })
                            ]
                        });

                        delegate = container.down('#foo');

                        container.disableTabbing();
                    });

                    it("should disable tabbing", function() {
                        expect(delegate.el.isTabbable()).toBe(false);
                    });

                    it("should re-enable tabbing", function() {
                        container.enableTabbing();

                        expect(delegate.el.isTabbable()).toBe(true);
                    });
                });

                // We're simulating a window here
                describe("focusable container with delegate", function() {
                    var delegate;

                    beforeEach(function() {
                        makeContainer({
                            floating: true,
                            focusable: true,
                            tabIndex: 0,
                            defaultFocus: 'bar',
                            items: [
                                childComponent({ itemId: 'bar' })
                            ]
                        });

                        delegate = container.down('#bar');

                        container.disableTabbing();
                    });

                    it("should disable tabbing on container", function() {
                        expect(container.el.isTabbable()).toBe(false);
                    });

                    it("should disable tabbing on delegate", function() {
                        expect(delegate.el.isTabbable()).toBe(false);
                    });

                    describe("re-enable", function() {
                        beforeEach(function() {
                            container.enableTabbing();
                        });

                        it("should enable tabbing on container", function() {
                            expect(container.el.isTabbable()).toBe(true);
                        });

                        it("should enable tabbing on delegate", function() {
                            expect(delegate.el.isTabbable()).toBe(true);
                        });
                    });
                });

                describe("focusEl outside of component DOM", function() {
                    beforeEach(function() {
                        makeComponent();

                        c.focusEl = Ext.getBody().appendChild({
                            tag: 'input',
                            type: 'button',
                            value: 'blerg'
                        });

                        c.disableTabbing();
                    });

                    afterEach(function() {
                        c.focusEl.destroy();
                    });

                    it("should disable tabbing", function() {
                        expect(c.focusEl.isTabbable()).toBe(false);
                    });

                    it("should re-enable tabbing", function() {
                        c.enableTabbing();

                        expect(c.focusEl.isTabbable()).toBe(true);
                    });
                });
            });

            describe("revertFocus", function() {
                var fooCmp, barCmp;

                beforeEach(function() {
                    makeContainer({
                        defaultFocus: '#foo',
                        items: [{
                            xtype: 'container',
                            itemId: 'fooContainer',
                            defaultFocus: '#foo',
                            items: [
                                childComponent({ itemId: 'foo' })
                            ]
                        }, {
                            xtype: 'container',
                            itemId: 'barContainer',
                            items: [
                                childComponent({ itemId: 'bar' })
                            ]
                        }]
                    });

                    fooCmp = container.down('#foo');
                    barCmp = container.down('#bar');
                });

                it("should work when target is a focus delegate", function() {
                    focusAndWait(barCmp);

                    runs(function() {
                        barCmp.hide();
                    });

                    expectFocused(fooCmp);
                });
            });

            // TODO https://sencha.jira.com/browse/EXT-68
            (isModern ? xdescribe : describe)("Focus and state changes", function() {
                var panel, fieldset, textfield1, textfield2, button1, button2;

                beforeEach(function() {
                    panel = new Ext.panel.Panel({
                        renderTo: document.body,

                        items: [{
                            xtype: 'textfield',
                            id: 'textfield1'
                        }, {
                            xtype: 'fieldset',
                            id: 'fieldset',
                            defaultFocus: 'textfield',
                            items: [{
                                xtype: 'textfield',
                                id: 'textfield2'
                            }]
                        }, {
                            // NOT toolbar here! Toolbars are FocusableContainers,
                            // which adds its own share of complexity
                            xtype: 'container',
                            docked: 'bottom',

                            items: [{
                                xtype: 'button',
                                id: 'button1',
                                text: 'Button 1',
                                focusable: true,
                                tabIndex: 0
                            }, {
                                xtype: 'button',
                                id: 'button2',
                                text: 'Button 2',
                                focusable: true,
                                tabIndex: 0
                            }]
                        }]
                    });

                    fieldset = panel.down('#fieldset');
                    textfield1 = panel.down('#textfield1');
                    textfield2 = panel.down('#textfield2');
                    button1 = panel.down('#button1');
                    button2 = panel.down('#button2');
                });

                afterEach(function() {
                    panel.destroy();
                    panel = fieldset = textfield1 = textfield2 = button1 = button2 = null;
                });

                describe("disabling focused component", function() {
                    it("should move focus to next sibling", function() {
                        focusAndWait(button1);

                        runs(function() {
                            // Disabling b1 should call button2.focus()
                            button1.disable();
                        });

                        expectFocused(button2);
                    });

                    it("should move focus to previous sibling", function() {
                        focusAndWait(button2);

                        runs(function() {
                            // Disabling b2 should call button1.focus()
                            button2.disable();
                        });

                        expectFocused(button1);
                    });

                    // TODO https://sencha.jira.com/browse/EXT-205
                    (isModern ? xit : it)("should move focus to a relation in parent container", function() {
                        focusAndWait(button2);

                        runs(function() {
                            button1.disable();

                            // Disabling b2 should call textfield.focus()
                            button2.disable();
                        });
                    });
                });

                describe("focusing disabled component", function() {
                    it("should move focus to next sibling", function() {
                        runs(function() {
                            button1.disable();
                        });

                        // IEs need a small delay after disabling
                        jasmine.waitAWhile();

                        runs(function() {
                            button1.focus();
                        });

                        expectFocused(button2);
                    });

                    it("should move focus to previous sibling", function() {
                        runs(function() {
                            button2.disable();
                        });

                        // IEs need a small delay after disabling
                        jasmine.waitAWhile();

                        runs(function() {
                            button2.focus();
                        });

                        expectFocused(button1);
                    });

                    // TODO https://sencha.jira.com/browse/EXT-205
                    (isModern ? xit : it)("should move focus to a relation in parent container", function() {
                        runs(function() {
                            button1.disable();
                            button2.disable();
                        });

                        // IEs need a small delay after disabling
                        jasmine.waitAWhile();

                        runs(function() {
                            button1.focus();
                        });

                        expectFocused(textfield1);
                    });
                });

                // TODO https://sencha.jira.com/browse/EXT-205
                (isModern ? xdescribe : describe)("hiding component that contains focus", function() {
                    it("should move focus to a relation or the previously focused component", function() {
                        focusAndWait(button1);

                        // Focus enters the fieldset, and the previously focused component
                        // (button1) should be cached at that point.
                        focusAndWait(textfield2);

                        runs(function() {
                            button1.disable();
                        });

                        // IEs need a small delay after disabling
                        jasmine.waitAWhile();

                        // The hide should attempt to revert focus back to button1.
                        // But now that is disabled, it should go to button2
                        runs(function() {
                            fieldset.hide();
                        });

                        expectFocused(button2);
                    });
                });
            });

            (isModern ? xdescribe : describe)("Wrapping a Component which contains focus", function() {
                var container, cmp, newEl;

                beforeEach(function() {
                    container = new Ext.Container({
                        items: {
                            xtype: 'textfield'
                        },
                        renderTo: document.body
                    });

                    cmp = container.child();

                    spyOn(container, 'onFocusEnter').andCallThrough();
                    spyOn(container, 'onFocusLeave').andCallThrough();
                    spyOn(cmp, 'onFocusEnter').andCallThrough();
                    spyOn(cmp, 'onFocusLeave').andCallThrough();

                    // Nudge input element to be repainted so it could focus
                    if (Ext.isIE8) {
                        // eslint-disable-next-line no-unused-expressions
                        +cmp.el.dom.offsetHeight;
                    }

                    cmp.focus();

                    // This will fail the tests if cmp doesn't focus,
                    // so we don't have to expect() it explicitly
                    waitForFocus(cmp);
                });

                afterEach(function() {
                    Ext.destroy(container, newEl);

                    container = cmp = newEl = null;
                });

                describe("wrapping", function() {
                    beforeEach(function() {
                        // These were tripped by focusing the cmp above
                        container.onFocusEnter.reset();
                        container.onFocusLeave.reset();
                        cmp.onFocusEnter.reset();
                        cmp.onFocusLeave.reset();

                        newEl = container.el.wrap();

                        // Wait for a possible (it would be a bug) focus leave or enter of the component.
                        // We can't wait for something, because we want NOTHING to happen.
                        waits(100);
                    });

                    it("should retain focus on the Component", function() {
                        expectFocused(cmp, true);
                    });

                    it("should retain hasFocus flag on the Component", function() {
                        expect(cmp.hasFocus).toBe(true);
                    });

                    it("should retain containsFocus flag on the container", function() {
                        expect(container.containsFocus).toBe(true);
                    });

                    it("should not call onFocusEnter on the container", function() {
                        expect(container.onFocusEnter).not.toHaveBeenCalled();
                    });

                    it("should not call onFocusLeave on the container", function() {
                        expect(container.onFocusLeave).not.toHaveBeenCalled();
                    });

                    it("should not call onFocusEnter on the Component", function() {
                        expect(cmp.onFocusEnter).not.toHaveBeenCalled();
                    });

                    it("should not call onFocusLeave on the Component", function() {
                        expect(cmp.onFocusLeave).not.toHaveBeenCalled();
                    });

                    describe("unwrapping", function() {
                        beforeEach(function() {
                            container.el.unwrap();

                            waits(100);
                        });

                        it("should retain focus on the Component", function() {
                            expectFocused(cmp, true);
                        });

                        it("should retain hasFocus flag on the Component", function() {
                            expect(cmp.hasFocus).toBe(true);
                        });

                        it("should retain containsFocus flag on the container", function() {
                            expect(container.containsFocus).toBe(true);
                        });

                        it("should not call onFocusEnter on the container", function() {
                            expect(container.onFocusEnter).not.toHaveBeenCalled();
                        });

                        it("should not call onFocusLeave on the container", function() {
                            expect(container.onFocusLeave).not.toHaveBeenCalled();
                        });

                        it("should not call onFocusEnter on the Component", function() {
                            expect(cmp.onFocusEnter).not.toHaveBeenCalled();
                        });

                        it("should not call onFocusLeave on the Component", function() {
                            expect(cmp.onFocusLeave).not.toHaveBeenCalled();
                        });
                    });
                });
            });
        });
    }

    makeSuite('Widget');
    makeSuite('Component');

    describe("keyboardMode", function() {
        var cls = Ext.baseCSSPrefix + 'keyboard-mode',
            body, oldMode;

        beforeAll(function() {
            // It's just a convenience ref, no need to clean up
            body = Ext.getBody();
        });

        beforeEach(function() {
            oldMode = Ext.getEnableKeyboardMode();
        });

        afterEach(function() {
            Ext.setEnableKeyboardMode(oldMode);
        });

        describe("defaults", function() {
            (!Ext.isModern && Ext.os.is.Desktop ? describe : xdescribe)("Classic desktop devices", function() {
                it("enableKeyboardMode config should be disabled", function() {
                    expect(Ext.enableKeyboardMode).toBe(false);
                });

                it("document body should have keyboardModeCls applied", function() {
                    expect(body).toHaveCls(cls);
                });
            });

            (Ext.isModern || !Ext.os.is.Desktop ? describe : xdescribe)("Modern or Classic non-desktop devices", function() {
                it("enableKeyboardMode should be enabled", function() {
                    expect(Ext.enableKeyboardMode).toBe(true);
                });

                it("document body should not have keyboardModeCls applied", function() {
                    expect(body).not.toHaveCls(cls);
                });
            });
        });

        describe("focus style handling", function() {
            var button1, button2;

            beforeEach(function() {
                button1 = new Ext.Button({
                    text: 'foo',
                    renderTo: body
                });

                button2 = new Ext.Button({
                    text: 'bar',
                    renderTo: body
                });
            });

            afterEach(function() {
                button1 = button2 = Ext.destroy(button1, button2);
            });

            (!Ext.isModern && Ext.os.is.Desktop ? describe : xdescribe)("with keyboardMode disabled", function() {
                beforeEach(function() {
                    Ext.setEnableKeyboardMode(false);

                    focusAndWait(button1);
                });

                it("body should have keyboardModeCls when button1 is focused", function() {
                    expect(body).toHaveCls(cls);
                });

                it("body should have keyboardModeCls when button2.focus() is called", function() {
                    button2.focus();

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).toHaveCls(cls);
                    });
                });

                it("body should have keyboardModeCls when button2 is clicked", function() {
                    jasmine.fireMouseEvent(button2, 'click');

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).toHaveCls(cls);
                    });
                });

                it("body should have keyboardModeCls when button2 is tabbed to", function() {
                    pressTabKey(button1);

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).toHaveCls(cls);
                    });
                });
            });

            (Ext.isModern || Ext.os.is.Desktop ? describe : xdescribe)("with keyboardMode enabled", function() {
                beforeEach(function() {
                    Ext.setEnableKeyboardMode(true);

                    focusAndWait(button1);
                });

                it("body should not have keyboardModeCls when button1 is focused", function() {
                    expect(body).not.toHaveCls(cls);
                });

                it("body should not have keyboardModeCls when button2.focus() is called", function() {
                    button2.focus();

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).not.toHaveCls(cls);
                    });
                });

                it("body should not have keyboardModeCls when button2 is clicked", function() {
                    jasmine.fireMouseEvent(button2, 'click');

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).not.toHaveCls(cls);
                    });
                });

                it("body should have keyboardModeCls when button2 is tabbed to", function() {
                    pressTabKey(button1);

                    waitForFocus(button2);

                    runs(function() {
                        expect(body).toHaveCls(cls);
                    });
                });
            });
        });
    });
});
