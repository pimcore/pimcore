// Although Ext.form.field.Trigger is deprecated, these specs remain as they were in 4.x
// so that we can have a reasonable assurance of compatibility
topSuite("Ext.form.field.Trigger", function() {
    var itNotTouch = jasmine.supportsTouch ? xit : it,
        component, makeComponent;

    beforeEach(function() {
        makeComponent = function(config) {
            config = config || {};
            Ext.applyIf(config, {
                name: 'test',
                width: 100
            });

            // Suppress console warning about Trigger field being deprecated
            spyOn(Ext.log, 'warn');

            component = new Ext.form.field.Trigger(config);
        };
    });

    afterEach(function() {
        if (component) {
            component.destroy();
        }

        component = makeComponent = null;
    });

    /**
     * Utility to dispatch a click event to the given element
     */
    function clickOn(el) {
        var xy = Ext.fly(el).getXY();

        jasmine.fireMouseEvent(el, 'click', xy[0], xy[1]);
    }

    it("should be registered with xtype 'triggerfield'", function() {
        // Suppress console warning about Trigger field being deprecated
        spyOn(Ext.log, 'warn');

        component = Ext.create("Ext.form.field.Trigger", { name: 'test' });
        expect(component instanceof Ext.form.field.Trigger).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("triggerfield");
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });
        it("should have hideTrigger = false", function() {
            expect(component.hideTrigger).toBe(false);
        });
        it("should have editable = true", function() {
            expect(component.editable).toBe(true);
        });
        it("should have readOnly = false", function() {
            expect(component.readOnly).toBe(false);
        });
    });

    describe("rendering", function() {
        beforeEach(function() {
            makeComponent({
                triggerCls: 'my-triggerCls',
                renderTo: Ext.getBody()
            });
        });

        describe("triggerWrap", function() {
            it("should be defined", function() {
                expect(component.triggerWrap).toBeDefined();
            });
            it("should be a child of the bodyEl", function() {
                expect(component.triggerWrap.dom.parentNode === component.bodyEl.dom).toBe(true);
            });
            it("should have a class of 'x-form-trigger-wrap'", function() {
                expect(component.triggerWrap.hasCls('x-form-trigger-wrap')).toBe(true);
            });
        });

        describe("triggerEl", function() {
            it("should be defined", function() {
                expect(component.triggerEl).toBeDefined();
            });
            it("should be a CompositeElement", function() {
                expect(component.triggerEl instanceof Ext.CompositeElement).toBe(true);
            });

            it("should give the trigger a class of 'x-form-trigger'", function() {
                expect(component.getTrigger('trigger1').el).toHaveCls('x-form-trigger');
            });
            it("should give the trigger a class matching the 'triggerCls' config", function() {
                expect(component.getTrigger('trigger1').el).toHaveCls('my-triggerCls');
            });

            // TODO multiple triggers
        });
    });

    describe("onTriggerClick method", function() {
        var spy;

        beforeEach(function() {
            makeComponent({
                renderTo: Ext.getBody(),
                onTriggerClick: (spy = jasmine.createSpy())
            });
        });

        it("should be called when the trigger is clicked", function() {
            clickOn(component.getTrigger('trigger1').el.dom);
            expect(spy).toHaveBeenCalled();
        });

        it("should be passed the Ext.EventObject for the click", function() {
            clickOn(component.getTrigger('trigger1').el.dom);
            expect(spy.mostRecentCall.args[2].browserEvent).toBeDefined();
        });
    });

    describe("trigger hiding", function() {
        var allTriggersHidden, allTriggersVisible, triggerHidden, triggerVisible;

        describe("hideTrigger config", function() {
            it("should hide the trigger elements when set to true", function() {
                makeComponent({
                    hideTrigger: true,
                    renderTo: Ext.getBody()
                });
                allTriggersHidden = true;
                component.triggerEl.each(function(e) {
                    if (e.isVisible()) {
                        allTriggersHidden = false;

                        return false;
                    }
                });
                expect(allTriggersHidden).toBe(true);
            });
            it("should not hide the trigger elements when set to false", function() {
                makeComponent({
                    hideTrigger: false,
                    renderTo: Ext.getBody()
                });
                allTriggersHidden = true;
                component.triggerEl.each(function(e) {
                    if (e.isVisible()) {
                        allTriggersHidden = false;

                        return false;
                    }
                });
                expect(allTriggersHidden).toBe(false);
            });
            it("should override any trigger elements when set to true", function() {
                makeComponent({
                    hideTrigger: true,
                    id: 'foo-field',
                    renderTo: Ext.getBody(),
                    triggers: {
                        trigger1: { hidden: false },
                        trigger2: { hidden: false },
                        trigger3: { hidden: false }
                    }
                });
                allTriggersHidden = true;
                component.triggerEl.each(function(e) {
                    if (e.isVisible()) {
                        allTriggersHidden = false;

                        return false;
                    }
                });
                expect(allTriggersHidden).toBe(true);
            });
            it("should override any trigger elements when set to false", function() {
                makeComponent({
                    hideTrigger: false,
                    id: 'foo-field',
                    renderTo: Ext.getBody(),
                    triggers: {
                        trigger1: { hidden: true },
                        trigger2: { hidden: true },
                        trigger3: { hidden: true }
                    }
                });
                allTriggersVisible = true;
                component.triggerEl.each(function(e) {
                    if (!e.isVisible()) {
                        allTriggersVisible = false;

                        return false;
                    }
                });
                expect(allTriggersVisible).toBe(true);
            });
        });

        describe("triggers config", function() {
            it("should hide all trigger elements except the second one", function() {
                makeComponent({
                    id: 'foo-field',
                    renderTo: Ext.getBody(),
                    triggers: {
                        trigger1: { hidden: true },
                        trigger2: { hidden: false },
                        trigger3: { hidden: true }
                    }
                });
                triggerVisible = 'Failed';
                component.triggerEl.each(function(e) { if (e.isVisible()) { triggerVisible = e.id; } });
                expect(triggerVisible).toBe('foo-field-trigger-trigger2');
            });
            it("should not hide all the trigger elements except second trigger", function() {
                makeComponent({
                    id: 'foo-field',
                    renderTo: Ext.getBody(),
                    triggers: {
                        trigger1: {},
                        trigger2: { hidden: true },
                        trigger3: {}
                    }
                });
                triggerHidden = 'Failed';
                component.triggerEl.each(function(e) { if (!e.isVisible()) { triggerHidden = e.id; } });
                expect(triggerHidden).toBe('foo-field-trigger-trigger2');
            });
        });

        describe("setHideTrigger method", function() {
            it("should hide the trigger elements when passed true", function() {
                makeComponent({
                    hideTrigger: false,
                    renderTo: Ext.getBody()
                });
                component.setHideTrigger(true);
                allTriggersHidden = true;
                component.triggerEl.each(function(e) {
                    if (e.isVisible()) {
                        allTriggersHidden = false;

                        return false;
                    }
                });
                expect(allTriggersHidden).toBe(true);
            });
            it("should hide the trigger elements when passed true, with triggers config", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggers: {
                        trigger1: { hidden: false },
                        trigger2: { hidden: false },
                        trigger3: { hidden: false }
                    }
                });
                component.setHideTrigger(true);
                allTriggersHidden = true;
                component.triggerEl.each(function(e) {
                    if (e.isVisible()) {
                        allTriggersHidden = false;

                        return false;
                    }
                });
                expect(allTriggersHidden).toBe(true);
            });
            it("should unhide the trigger elements when passed false", function() {
                makeComponent({
                    hideTrigger: true,
                    renderTo: Ext.getBody()
                });
                component.setHideTrigger(false);
                allTriggersVisible = true;
                component.triggerEl.each(function(e) {
                    if (!e.isVisible()) {
                        allTriggersVisible = false;

                        return false;
                    }
                });
                expect(allTriggersVisible).toBe(true);
            });

            describe('before render', function() {
                it("should hide the trigger if set in initComponent", function() {
                    makeComponent({
                        hideTrigger: false,
                        xhooks: {
                            initComponent: function() {
                                this.setHideTrigger(true);
                                this.callParent();
                            }
                        },
                        renderTo: Ext.getBody()
                    });
                    allTriggersHidden = true;
                    component.triggerEl.each(function(e) {
                        if (e.isVisible()) {
                            allTriggersHidden = false;

                            return false;
                        }
                    });
                    expect(allTriggersHidden).toBe(true);
                });

                it("should unhide the trigger if set in initComponent", function() {
                    makeComponent({
                        hideTrigger: true,
                        xhooks: {
                            initComponent: function() {
                                this.setHideTrigger(false);
                                this.callParent();
                            }
                        },
                        renderTo: Ext.getBody()
                    });
                    allTriggersVisible = true;
                    component.triggerEl.each(function(e) {
                        if (!e.isVisible()) {
                            allTriggersVisible = false;

                            return false;
                        }
                    });
                    expect(allTriggersVisible).toBe(true);
                });
            });
        });
    });

    describe("editable", function() {
        describe("editable config", function() {
            it("should set the input to readOnly when set to false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    editable: false
                });
                expect(component.inputEl.dom.readOnly + '').toEqual('true');
            });

            it("should not set the input to readOnly when set to true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    editable: true
                });
                expect(component.inputEl.dom.readOnly + '').toEqual('false');
            });
        });

        describe("setEditable method", function() {
            it("should set the input to readOnly when passed false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    editable: true
                });
                component.setEditable(false);
                expect(component.inputEl.dom.readOnly + '').toEqual('true');
            });

            it("should not set the input to readOnly when passed true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    editable: false
                });
                component.setEditable(true);
                expect(component.inputEl.dom.readOnly + '').toEqual('false');
            });
        });
    });

    describe("readOnly", function() {
        var spy;

        describe("readOnly config", function() {
            it("should set the input to readOnly when set to true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true
                });

                expect(component.inputEl.dom.readOnly + '').toEqual('true');
            });

            it("should not call the onTriggerClick method upon clicking the trigger, when set to true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true,
                    onTriggerClick: (spy = jasmine.createSpy())
                });

                var trigger = component.getTrigger('trigger1');

                expect(trigger.isVisible()).toBe(false);
                clickOn(trigger.el.dom);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not set the input to readOnly when set to false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: false
                });
                expect(component.inputEl.dom.readOnly + '').toEqual('false');
            });

            it("should hide trigger when readOnly when set to true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true
                });
                expect(component.getTrigger('trigger1').isVisible()).toBe(false);
            });

            it("should not hide trigger when readOnly set to true, but trigger configured not to do so", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true,
                    triggers: {
                        trigger1: { hideOnReadOnly: false }
                    }
                });
                expect(component.getTrigger('trigger1').isVisible()).toBe(true);
            });

            it("should call the onTriggerClick method upon clicking the trigger, when set to false", function() {
                var spy;

                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: false,
                    onTriggerClick: (spy = jasmine.createSpy())
                });
                clickOn(component.getTrigger('trigger1').el.dom);
                expect(spy).toHaveBeenCalled();
            });
        });

        describe("setReadOnly method", function() {
            it("should set the input to readOnly when passing true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: false
                });
                component.setReadOnly(true);
                expect(component.inputEl.dom.readOnly + '').toEqual('true');
            });

            it("should not call the onTriggerClick method upon clicking the trigger, when passing true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: false,
                    onTriggerClick: (spy = jasmine.createSpy())
                });
                component.setReadOnly(true);
                clickOn(component.getTrigger('trigger1').el.dom);
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not set the input to readOnly when passing false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true
                });
                component.setReadOnly(false);
                expect(component.inputEl.dom.readOnly + '').toEqual('false');
            });

            it("should not hide trigger when readOnly set to true, but trigger configured not to do so", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: false,
                    triggers: {
                        trigger1: { hideOnReadOnly: false }
                    }
                });
                component.setReadOnly(true);
                expect(component.getTrigger('trigger1').isVisible()).toBe(true);
            });

            it("should call the onTriggerClick method upon clicking the trigger, when passing false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    readOnly: true,
                    onTriggerClick: (spy = jasmine.createSpy())
                });
                component.setReadOnly(false);
                clickOn(component.getTrigger('trigger1').el.dom);
                expect(spy).toHaveBeenCalled();
            });
        });
    });

    // Focus issues in the test runner
    (Ext.isWebKit ? describe : xdescribe)("focus/blur", function() {
        it("should blur when focusing another field", function() {
            var called = false,
                tf;

            makeComponent({
                renderTo: Ext.getBody(),
                listeners: {
                    blur: function() {
                        called = true;
                    }
                }
            });

            tf = new Ext.form.field.Text({
                renderTo: Ext.getBody()
            });

            component.focus();
            expect(component.hasFocus).toBe(true);
            tf.focus();
            waits(1);
            runs(function() {
                expect(called).toBe(true);
                expect(component.hasFocus).toBe(false);
                expect(Ext.Element.getActiveElement()).toBe(tf.inputEl.dom);
                tf.destroy();
            });
        });

        it("should not blur when the trigger element is clicked.", function() {
            var called = false;

            makeComponent({
                renderTo: Ext.getBody()
            });

            component.focus();
            component.on('blur', function() {
                called = true;
            });

            jasmine.fireMouseEvent(component.getTrigger('trigger1').el.dom, 'click');

            expect(called).toBe(false);
            expect(component.hasFocus).toBe(true);
            expect(Ext.Element.getActiveElement()).toBe(component.inputEl.dom);
        });
    });

    describe("trigger classes", function() {
        function triggerEvent(type, idx, x, y, button) {
            var el = component.triggerEl.item(idx);

            jasmine.fireMouseEvent(el.dom, type, x, y, button);
        }

        function hasCls(cls, idx) {
            return component.triggerEl.item(idx).hasCls(cls);
        }

        // Need to trigger different synthetic events for IE
        var overEvent = Ext.supports.MouseEnterLeave ? 'mouseenter' : 'mouseover',
            outEvent = Ext.supports.MouseEnterLeave ? 'mouseleave' : 'mouseout',
            baseCls = Ext.form.trigger.Trigger.prototype.baseCls;

        describe("single trigger", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
            });

            itNotTouch("should add the base overCls on mouseover", function() {
                triggerEvent(overEvent, 0);
                expect(hasCls(baseCls + '-over', 0)).toBe(true);
            });

            itNotTouch("should remove the base overCls on mouseout", function() {
                triggerEvent(overEvent, 0);
                triggerEvent(outEvent, 0);
                expect(hasCls(baseCls + '-over', 0)).toBe(false);
            });

            it("should add the base clickCls on mousedown", function() {
                triggerEvent('mousedown', 0);
                expect(hasCls(baseCls + '-click', 0)).toBe(true);
                triggerEvent('mouseup', 0);
            });

            it("should remove the base clickCls on mouseup", function() {
                triggerEvent('mousedown', 0);
                triggerEvent('mouseup', 0);
                expect(hasCls(baseCls + '-click', 0)).toBe(false);
            });
        });

        describe("multi trigger", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    trigger2Cls: 'foo',
                    onTrigger2Click: Ext.emptyFn
                });
            });

            itNotTouch("should add the base overCls on mouseover to the 2nd trigger", function() {
                triggerEvent(overEvent, 1);
                expect(hasCls(baseCls + '-over', 1)).toBe(true);
            });

            itNotTouch("should remove the base overCls on mouseout", function() {
                triggerEvent(overEvent, 1);
                triggerEvent(outEvent, 1);
                expect(hasCls(baseCls + '-over', 1)).toBe(false);
            });

            it("should add the base clickCls on mousedown", function() {
                triggerEvent('mousedown', 1);
                expect(hasCls(baseCls + '-click', 1)).toBe(true);
                triggerEvent('mouseup', 1);
            });

            it("should remove the base clickCls on mouseup", function() {
                triggerEvent('mousedown', 1);
                triggerEvent('mouseup', 1);
                expect(hasCls(baseCls + '-click', 1)).toBe(false);
            });
        });

        describe("custom trigger cls", function() {
            it("should add a custom overCls on mouseover if specified", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggerCls: 'bar'
                });
                triggerEvent(overEvent, 0);
                expect(hasCls('bar-over', 0)).toBe(true);
            });

            it("should remove a custom overCls on mouseout if specified", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggerCls: 'bar'
                });
                triggerEvent(overEvent, 0);
                triggerEvent(outEvent, 0);
                expect(hasCls('bar-over', 0)).toBe(false);
            });

            it("should add a custom clickCls on mousedown if specified", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggerCls: 'bar'
                });
                triggerEvent('mousedown', 0);
                expect(hasCls('bar-click', 0)).toBe(true);
                triggerEvent('mouseup', 0);
            });

            it("should remove a custom clickCls on mouseup if specified", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    triggerCls: 'bar'
                });
                triggerEvent('mousedown', 0);
                triggerEvent('mouseup', 0);
                expect(hasCls('bar-click', 0)).toBe(false);
            });

            it("should not attempt to add an overCls if none exists", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                triggerEvent(overEvent, 0);
                expect(hasCls('undefined-over', 0)).toBe(false);
            });

            it("should not attempt to add a clickCls if none exists", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                triggerEvent('mousedown', 0);
                expect(hasCls('undefined-over', 0)).toBe(false);
                triggerEvent('mouseup', 0);
            });
        });
    });
});
