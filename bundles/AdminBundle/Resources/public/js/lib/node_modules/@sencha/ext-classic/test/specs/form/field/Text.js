topSuite("Ext.form.field.Text",
    ['Ext.form.field.Display', 'Ext.form.field.Checkbox', 'Ext.Panel',
     'Ext.app.ViewModel', 'Ext.Button', 'Ext.data.validator.*', 'Ext.field.InputMask'],
function() {
    var component;

    function makeComponent(config) {
        config = config || {};

        if (!config.name) {
            config.name = 'test';
        }

        return component = new Ext.form.field.Text(config);
    }

    function render(parent) {
        component.render(parent || Ext.getBody());
    }

    function createField(config) {
        config = Ext.apply({
            name: 'fieldName',
            value: 'fieldValue',
            tabIndex: 5,
            size: 12,
            renderTo: Ext.getBody()
        }, config);

        // Suppress console warning about 'size' config being deprecated
        spyOn(Ext.log, 'warn');

        return makeComponent(config);
    }

    afterEach(function() {
        if (component) {
            component.destroy();
            component = null;
        }
    });

    describe("alternate class name", function() {
        it("should have Ext.form.TextField as the alternate class name", function() {
            expect(Ext.form.field.Text.prototype.alternateClassName).toEqual(["Ext.form.TextField", "Ext.form.Text"]);
        });

        it("should allow the use of Ext.form.TextField", function() {
            expect(Ext.form.TextField).toBeDefined();
        });
    });

    it("should be registered as 'textfield' xtype", function() {
        component = Ext.create("Ext.form.field.Text", { name: 'test' });
        expect(component instanceof Ext.form.field.Text).toBe(true);
        expect(Ext.getClass(component).xtype).toBe("textfield");
    });

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have inputType = 'text'", function() {
            expect(component.inputType).toBe('text');
        });
        it("should have vtypeText = undefined", function() {
            expect(component.vtypeText).not.toBeDefined();
        });
        it("should have stripCharsRe = undefined", function() {
            expect(component.stripCharsRe).not.toBeDefined();
        });
        it("should have grow = falsy", function() {
            expect(component.grow).toBeFalsy();
        });
        it("should have growMin = 30", function() {
            expect(component.growMin).toBe(30);
        });
        it("should have growMax = 800", function() {
            expect(component.growMax).toBe(800);
        });
        it("should have vtype = undefined", function() {
            expect(component.vtype).not.toBeDefined();
        });
        it("should have maskRe = undefined", function() {
            expect(component.maskRe).not.toBeDefined();
        });
        it("should have disableKeyFilter = falsy", function() {
            expect(component.disableKeyFilter).toBeFalsy();
        });
        it("should have allowBlank = true", function() {
            expect(component.allowBlank).toBe(true);
        });
        it("should have minLength = 0", function() {
            expect(component.minLength).toBe(0);
        });
        it("should have maxLength = MAX_VALUE", function() {
            expect(component.maxLength).toBe(Number.MAX_VALUE);
        });
        it("should have enforceMaxLength = falsy", function() {
            expect(component.enforceMaxLength).toBeFalsy();
        });
        it("should have minLengthText = 'The minimum length for this field is {0}'", function() {
            expect(component.minLengthText).toBe('The minimum length for this field is {0}');
        });
        it("should have maxLengthText = 'The maximum length for this field is {0}'", function() {
            expect(component.maxLengthText).toBe('The maximum length for this field is {0}');
        });
        it("should have selectOnFocus = falsy", function() {
            expect(component.selectOnFocus).toBeFalsy();
        });
        it("should have blankText = 'This field is required'", function() {
            expect(component.blankText).toBe('This field is required');
        });
        it("should have validator = undefined", function() {
            expect(component.vtypeText).not.toBeDefined();
        });
        it("should have regex = undefined", function() {
            expect(component.regex).not.toBeDefined();
        });
        it("should have regexText = ''", function() {
            expect(component.regexText).toBe('');
        });
        it("should have emptyText = ''", function() {
            expect(component.emptyText).toBe('');
        });
        it("should have emptyCls = 'x-form-empty-field'", function() {
            expect(component.emptyCls).toBe('x-form-empty-field');
        });
        it("should have enableKeyEvents = falsy", function() {
            expect(component.enableKeyEvents).toBeFalsy();
        });
    });

    describe("inputMask", function() {
        it("should create an InputMask", function() {
            makeComponent({
                inputMask: '(999) 999-9999'
            });

            expect(component.getInputMask().getPattern()).toBe('(999) 999-9999');
        });

        it("should add the mask on focus", function() {
            makeComponent({
                inputMask: '(999) 999-9999',
                renderTo: document.body
            });

            jasmine.focusAndWait(component.inputEl);

            runs(function() {
                expect(component.inputEl.dom.value).toBe('(___) ___-____');
            });
        });

        // TODO This test is unreliable
        (Ext.isiOS || Ext.isAndroid ? xit : xit)("should clear the field on blur", function() {
            makeComponent({
                inputMask: '(999) 999-9999',
                renderTo: document.body
            });

            jasmine.focusAndWait(component.inputEl);
            jasmine.blurAndWait(component);

            runs(function() {
                expect(component.inputEl.dom.value).toBe('');
            });
        });

        (Ext.isIE8 || Ext.isAndroid ? xdescribe : describe)("paste", function() {
            it("should format value", function() {
                makeComponent({
                    inputMask: '(999) 999-9999',
                    enableKeyEvents: true,
                    renderTo: document.body
                });

                jasmine.focusAndWait(component.inputEl);

                var e = {
                    browserEvent: {
                        clipboardData: {
                            getData: function() {
                                return '1234567890';
                            }
                        }
                    },
                    preventDefault: Ext.emptyFn
                };

                runs(function() {
                    component.inputEl.fireEvent('paste', e);
                    expect(component.inputEl.dom.value).toBe('(123) 456-7890');
                });
            });

            it("should not change a formatted value", function() {
                makeComponent({
                    inputMask: '(999) 999-9999',
                    enableKeyEvents: true,
                    renderTo: document.body
                });

                jasmine.focusAndWait(component.inputEl);

                var e = {
                    browserEvent: {
                        clipboardData: {
                            getData: function() {
                                return '(123) 456-7890';
                            }
                        }
                    },
                    preventDefault: Ext.emptyFn
                };

                runs(function() {
                    component.inputEl.fireEvent('paste', e);
                    expect(component.inputEl.dom.value).toBe('(123) 456-7890');
                });
            });

            it("should not format invalid values", function() {
                makeComponent({
                    inputMask: '(999) 999-9999',
                    enableKeyEvents: true,
                    renderTo: document.body
                });

                jasmine.focusAndWait(component.inputEl);

                var e = {
                    browserEvent: {
                        clipboardData: {
                            getData: function() {
                                return 'abcd';
                            }
                        }
                    },
                    preventDefault: Ext.emptyFn
                };

                runs(function() {
                    component.inputEl.fireEvent('paste', e);
                    expect(component.inputEl.dom.value).toBe('(___) ___-____');
                });
            });
        });
    });

    it("should encode the input value in the template", function() {
        makeComponent({
            renderTo: Ext.getBody(),
            value: 'test "  <br/> test'
        });
        expect(component.inputEl.dom.value).toBe('test "  <br/> test');
    });

    it("should be able to set a numeric value", function() {
        makeComponent({
            renderTo: Ext.getBody()
        });
        component.setValue(100);
        expect(component.getValue()).toBe('100');
    });

    it("should be able to set a value in the render event", function() {
        makeComponent({
            renderTo: Ext.getBody(),
            listeners: {
                render: function(c) {
                    c.setValue('foo');
                }
            }
        });
        expect(component.getValue()).toBe('foo');
        expect(component.inputEl.dom.value).toBe('foo');
    });

    describe("rendering", function() {
        // NOTE this doesn't yet test the main label, error icon, etc. just the parts specific to Text.
        describe('should work', function() {
            beforeEach(function() {
                createField({
                    afterSubTpl: ['<h1 id="{id}-afterSubEl" data-ref="afterSubEl">afterSubTpl</h1>'],
                    childEls: ['afterSubEl']
                });
            });

            afterEach(function() {
                component.destroy();
            });

            describe("afterSubEl", function() {
                it("should exist", function() {
                    expect(component.afterSubEl.dom.tagName.toUpperCase()).toBe('H1');
                });
                it("should have proper id", function() {
                    expect(component.afterSubEl.id).toBe(component.id + '-afterSubEl');
                });
            });

            describe("bodyEl", function() {
                it("should have the class 'x-form-item-body'", function() {
                    expect(component.bodyEl.hasCls('x-form-item-body')).toBe(true);
                });

                it("should have the id '[id]-bodyEl'", function() {
                    expect(component.bodyEl.dom.id).toEqual(component.id + '-bodyEl');
                });
            });

            describe("inputEl", function() {
                it("should be an input element", function() {
                    expect(component.inputEl.dom.tagName.toLowerCase()).toEqual('input');
                });

                it("should have type = the inputType config of the element", function() {
                    expect(component.inputEl.dom.type).toEqual(component.inputType);
                });

                it("should have the component's inputId as its id", function() {
                    expect(component.inputEl.dom.id).toEqual(component.inputId);
                });

                it("should be cached by its dom id", function() {
                    expect(Ext.cache[component.inputEl.dom.id]).not.toBe(undefined);
                });

                it("should be cached by its component inputId", function() {
                    expect(Ext.cache[component.inputId]).not.toBe(undefined);
                });

                it("should have the 'fieldCls' config as a class", function() {
                    expect(component.inputEl.hasCls(component.fieldCls)).toBe(true);
                });

                it("should have a class of 'x-form-[inputType]'", function() {
                    expect(component.inputEl.hasCls('x-form-' + component.inputType)).toBe(true);
                });

                it("should have its name set to the 'name' config", function() {
                    expect(component.inputEl.dom.name).toEqual('fieldName');
                });

                it("should have its value set to the 'value' config", function() {
                    expect(component.inputEl.dom.value).toEqual('fieldValue');
                });

                it("should have autocomplete = 'off'", function() {
                    expect(component.inputEl.dom.getAttribute('autocomplete')).toEqual('off');
                });

                it("should have tabindex set to the tabIndex config", function() {
                    expect('' + component.inputEl.dom.getAttribute("tabIndex")).toEqual('5');
                });

                it("should set the size attribute", function() {
                    expect(+component.inputEl.dom.getAttribute("size")).toEqual(1);
                });
            });

            describe("ariaEl", function() {
                it("should be inputEl", function() {
                    expect(component.ariaEl).toBe(component.inputEl);
                });
            });

            describe("sizing", function() {
                var panel, fields,
                    createPanel = function(cfg) {
                        panel = Ext.create('Ext.panel.Panel', Ext.apply({
                            width: 300,
                            defaults: {
                                margin: '0 0 20'
                            },
                            items: [{
                                xtype: 'textfield',
                                fieldLabel: 'label'
                            }, {
                                xtype: 'textfield',
                                fieldLabel: 'this is a really really really really long label'
                            }, {
                                xtype: 'textfield',
                                fieldLabel: 'heighted',
                                height: 200
                            }, {
                                xtype: 'textfield',
                                fieldLabel: 'flexed with really long label sflkdj skl fkdlsfj dlskjf klds j',
                                flex: 1
                            }],
                            renderTo: document.body
                        }, cfg));

                        fields = panel.items.getRange();
                    },
                    diff = Ext.isIE8 ? 2 : 0;

                afterEach(function() {
                    panel.destroy();
                    panel = fields = null;
                });

                describe("layout auto", function() {
                    beforeEach(function() {
                        createPanel();
                    });

                    it("should not expand the fields height when the label causes a line break", function() {
                        expect(fields[0].inputWrap.getHeight()).toBeGreaterThan(0);
                        expect(fields[1].inputWrap.getHeight()).toBe(fields[0].inputWrap.getHeight());
                    });

                    it("should respect the configured height", function() {
                        expect(fields[2].inputWrap.getHeight()).toBe(200 + diff);
                        // it should not flex the height
                        expect(fields[3].inputWrap.getHeight()).toBe(fields[0].inputWrap.getHeight());
                    });

                    it("should contain the heighted cls only when height is configured", function() {
                        expect(fields[0].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(false);
                        expect(fields[2].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(true);
                        // Flex should be ignored with Layout auto
                        expect(fields[3].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(false);
                    });
                });

                describe("layout vbox", function() {
                    describe("non-heighted", function() {
                        beforeEach(function() {
                            createPanel({
                                layout: 'vbox'
                            });
                        });

                        it("should not expand the fields height when the label causes a line break", function() {
                            expect(fields[0].inputWrap.getHeight()).toBeGreaterThan(0);
                            expect(fields[1].inputWrap.getHeight()).toBe(fields[0].inputWrap.getHeight());
                        });

                        it("should respect the configured height", function() {
                            expect(fields[2].inputWrap.getHeight()).toBe(200 + diff);
                            // it should not flex the height
                            expect(fields[3].inputWrap.getHeight()).toBe(fields[0].inputWrap.getHeight());
                        });

                        it("should contain the heighted cls only when height is configured", function() {
                            expect(fields[0].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(false);
                            expect(fields[2].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(true);
                            // Flex should be ignored with Layout auto
                            expect(fields[3].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(false);
                        });
                    });

                    describe("heighted", function() {
                        beforeEach(function() {
                            createPanel({
                                layout: 'vbox',
                                minHeight: 500
                            });
                        });

                        it("should not expand the fields height when the label causes a line break", function() {
                            expect(fields[0].inputWrap.getHeight()).toBeGreaterThan(0);
                            expect(fields[1].inputWrap.getHeight()).toBe(fields[0].inputWrap.getHeight());
                        });

                        (Ext.isIE8 ? xit : it)("should respect the configured height", function() {
                            var margins = 80 - diff,
                                innerCt = panel.el.down('[data-ref=innerCt]'); // 20px for each field

                            expect(fields[2].inputWrap.getHeight()).toBe(200 + diff);

                            if (Ext.isIE8) {
                                waitsFor(function() {
                                    return fields[3].inputWrap.getHeight() > 100;
                                }, 'layout to run', 100);
                            }

                            runs(function() {
                                expect(fields[3].inputWrap.getHeight() - diff).toBe(innerCt.getHeight() - fields[0].getHeight() - fields[1].getHeight() - fields[2].getHeight() - margins);
                            });

                        });

                        it("should contain the heighted cls only when height is configured", function() {
                            expect(fields[0].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(false);
                            expect(fields[2].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(true);
                            expect(fields[3].hasCls(Ext.baseCSSPrefix + 'form-text-heighted')).toBe(true);
                        });
                    });

                });
            });
        });

        // Text fields are extremely important so we're duplicating
        // the Base tests here
        describe("ARIA attributes", function() {
            describe("in general", function() {
                it("should not render when !ariaRole", function() {
                    createField({ ariaRole: undefined });

                    expect(component.ariaEl.dom.hasAttribute('role')).toBe(false);
                });

                it("should render when ariaRole is defined", function() {
                    createField();

                    expect(component).toHaveAttr('role', 'textbox');
                });
            });

            describe("aria-hidden", function() {
                it("should be false when visible", function() {
                    createField();

                    expect(component).toHaveAttr('aria-hidden', 'false');
                });

                it("should be true when hidden", function() {
                    createField({ hidden: true });

                    expect(component).toHaveAttr('aria-hidden', 'true');
                });
            });

            describe("aria-disabled", function() {
                it("should be false when enabled", function() {
                    createField();

                    expect(component).toHaveAttr('aria-disabled', 'false');
                });

                it("should be true when disabled", function() {
                    createField({ disabled: true });

                    expect(component).toHaveAttr('aria-disabled', 'true');
                });
            });

            describe("aria-readonly", function() {
                it("should be false by default", function() {
                    createField();

                    expect(component).toHaveAttr('aria-readonly', 'false');
                });

                it("should be true when readOnly", function() {
                    createField({ readOnly: true });

                    expect(component).toHaveAttr('aria-readonly', 'true');
                });
            });

            describe("aria-invalid", function() {
                it("should be false by default", function() {
                    createField();

                    expect(component).toHaveAttr('aria-invalid', 'false');
                });
            });

            describe("aria-label", function() {
                it("should not exist by default", function() {
                    createField();

                    expect(component).not.toHaveAttr('aria-label');
                });

                it("should be rendered when set", function() {
                    createField({ ariaLabel: 'foo' });

                    expect(component).toHaveAttr('aria-label', 'foo');
                });
            });

            describe("via config", function() {
                it("should set aria-foo", function() {
                    createField({
                        ariaAttributes: {
                            'aria-foo': 'bar'
                        }
                    });

                    expect(component).toHaveAttr('aria-foo', 'bar');
                });
            });
        });

        describe('labelPad', function() {
            it('should set a default right padding', function() {
                makeComponent({
                    fieldLabel: 'Name',
                    renderTo: Ext.getBody()
                });

                expect(component.labelEl.dom.style.paddingRight).toBe('5px');
            });

            it('should set the labelPad property on the field component', function() {
                makeComponent({
                    fieldLabel: 'Name',
                    renderTo: Ext.getBody()
                });

                expect(component.labelPad).toBe(5);
            });

            it('should set a right padding when labelAlign === left', function() {
                makeComponent({
                    fieldLabel: 'Name',
                    labelAlign: 'left', // default
                    labelPad: 100,
                    renderTo: Ext.getBody()
                });

                expect(component.labelEl.dom.style.paddingRight).toBe('100px');
            });

            it('should set a right padding when labelAlign === right', function() {
                makeComponent({
                    fieldLabel: 'Name',
                    labelAlign: 'right',
                    labelPad: 100,
                    renderTo: Ext.getBody()
                });

                expect(component.labelEl.dom.style.paddingRight).toBe('100px');
            });

            it('should set a bottom padding when labelAlign === top', function() {
                makeComponent({
                    fieldLabel: 'Name',
                    labelAlign: 'top',
                    labelPad: 20,
                    renderTo: Ext.getBody()
                });

                expect(component.labelEl.dom.firstChild.style.paddingBottom).toBe('20px');
            });
        });
    });

    describe("readOnly", function() {
        describe("readOnly config", function() {
            describe("readOnly: true", function() {
                it("should set the readOnly on the inputEl", function() {
                    makeComponent({
                        readOnly: true,
                        renderTo: Ext.getBody()
                    });
                    expect(component.inputEl.dom.readOnly).toBe(true);
                });

                it("should have triggers hidden", function() {
                    makeComponent({
                        readOnly: true,
                        triggers: {
                            foo: {},
                            bar: {}
                        },
                        renderTo: Ext.getBody()
                    });
                    expect(component.getTrigger('foo').hidden).toBe(true);
                    expect(component.getTrigger('bar').hidden).toBe(true);
                });

                it("should not fire the writeablechange event", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        readOnly: true,
                        renderTo: Ext.getBody(),
                        listeners: {
                            writeablechange: spy
                        }
                    });
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should set aria-readonly to true", function() {
                    createField({ readOnly: true });

                    expect(component).toHaveAttr('aria-readonly', 'true');
                });
            });

            describe("readOnly: false", function() {
                it("should not set the readOnly on the inputEl", function() {
                    makeComponent({
                        readOnly: false,
                        renderTo: Ext.getBody()
                    });
                    expect(component.inputEl.dom.readOnly).toBe(false);
                });

                it("should have triggers visible", function() {
                    makeComponent({
                        readOnly: false,
                        triggers: {
                            foo: {},
                            bar: {}
                        },
                        renderTo: Ext.getBody()
                    });
                    expect(component.getTrigger('foo').hidden).toBe(false);
                    expect(component.getTrigger('bar').hidden).toBe(false);
                });

                it("should not fire the writeablechange event", function() {
                    var spy = jasmine.createSpy();

                    makeComponent({
                        readOnly: false,
                        renderTo: Ext.getBody(),
                        listeners: {
                            writeablechange: spy
                        }
                    });
                    expect(spy).not.toHaveBeenCalled();
                });

                it("should set aria-readonly to false", function() {
                    createField({ readOnly: false });

                    expect(component).toHaveAttr('aria-readonly', 'false');
                });
            });
        });

        describe("setReadOnly method", function() {
            describe("before render", function() {
                describe("readOnly: true", function() {
                    it("should set readOnly on the inputEl when rendered", function() {
                        makeComponent();
                        component.setReadOnly(true);
                        component.render(Ext.getBody());
                        expect(component.inputEl.dom.readOnly).toBe(true);
                    });

                    it("should hide triggers when rendered", function() {
                        makeComponent({
                            triggers: {
                                foo: {},
                                bar: {}
                            }
                        });
                        component.setReadOnly(true);
                        component.render(Ext.getBody());
                        expect(component.getTrigger('foo').hidden).toBe(true);
                        expect(component.getTrigger('bar').hidden).toBe(true);
                    });

                    it("should fire the writeablechange event", function() {
                        var spy = jasmine.createSpy();

                        makeComponent();
                        component.on('writeablechange', spy);
                        component.setReadOnly(true);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(component);
                        expect(spy.mostRecentCall.args[1]).toBe(true);
                    });

                    it("should set aria-readonly to true", function() {
                        makeComponent();
                        component.setReadOnly(true);
                        component.render(Ext.getBody());

                        expect(component).toHaveAttr('aria-readonly', 'true');
                    });
                });

                describe("readOnly: false", function() {
                    it("should not set readOnly on the inputEl when rendered", function() {
                        makeComponent({
                            readOnly: true
                        });
                        component.setReadOnly(false);
                        component.render(Ext.getBody());
                        expect(component.inputEl.dom.readOnly).toBe(false);
                    });

                    it("should not hide triggers when rendered", function() {
                        makeComponent({
                            readOnly: true,
                            triggers: {
                                foo: {},
                                bar: {}
                            }
                        });
                        component.setReadOnly(false);
                        component.render(Ext.getBody());
                        expect(component.getTrigger('foo').hidden).toBe(false);
                        expect(component.getTrigger('bar').hidden).toBe(false);
                    });

                    it("should fire the writeablechange event", function() {
                        var spy = jasmine.createSpy();

                        makeComponent({
                            readOnly: true
                        });
                        component.on('writeablechange', spy);
                        component.setReadOnly(false);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(component);
                        expect(spy.mostRecentCall.args[1]).toBe(false);
                    });

                    it("should set aria-readonly to false", function() {
                        makeComponent({ readOnly: true });
                        component.setReadOnly(false);
                        component.render(Ext.getBody());

                        expect(component).toHaveAttr('aria-readonly', 'false');
                    });
                });
            });

            describe("after render", function() {
                describe("readOnly: true", function() {
                    it("should set readOnly on the inputEl", function() {
                        makeComponent({
                            renderTo: Ext.getBody()
                        });
                        component.setReadOnly(true);
                        expect(component.inputEl.dom.readOnly).toBe(true);
                    });

                    it("should hide triggers when rendered", function() {
                        makeComponent({
                            renderTo: Ext.getBody(),
                            triggers: {
                                foo: {},
                                bar: {}
                            }
                        });
                        component.setReadOnly(true);
                        expect(component.getTrigger('foo').hidden).toBe(true);
                        expect(component.getTrigger('bar').hidden).toBe(true);
                    });

                    it("should fire the writeablechange event", function() {
                        var spy = jasmine.createSpy();

                        makeComponent({
                            renderTo: Ext.getBody()
                        });
                        component.on('writeablechange', spy);
                        component.setReadOnly(true);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(component);
                        expect(spy.mostRecentCall.args[1]).toBe(true);
                    });

                    it("should set aria-readonly to true", function() {
                        createField();
                        component.setReadOnly(true);

                        expect(component).toHaveAttr('aria-readonly', 'true');
                    });
                });

                describe("readOnly: false", function() {
                    it("should not set readOnly on the inputEl when rendered", function() {
                        makeComponent({
                            renderTo: Ext.getBody(),
                            readOnly: true
                        });
                        component.setReadOnly(false);
                        expect(component.inputEl.dom.readOnly).toBe(false);
                    });

                    it("should not hide triggers when rendered", function() {
                        makeComponent({
                            renderTo: Ext.getBody(),
                            readOnly: true,
                            triggers: {
                                foo: {},
                                bar: {}
                            }
                        });
                        component.setReadOnly(false);
                        expect(component.getTrigger('foo').hidden).toBe(false);
                        expect(component.getTrigger('bar').hidden).toBe(false);
                    });

                    it("should fire the writeablechange event", function() {
                        var spy = jasmine.createSpy();

                        makeComponent({
                            renderTo: Ext.getBody(),
                            readOnly: true
                        });
                        component.on('writeablechange', spy);
                        component.setReadOnly(false);
                        expect(spy.callCount).toBe(1);
                        expect(spy.mostRecentCall.args[0]).toBe(component);
                        expect(spy.mostRecentCall.args[1]).toBe(false);
                    });

                    it("should set aria-readonly to false", function() {
                        createField({ readOnly: true });
                        component.setReadOnly(false);

                        expect(component).toHaveAttr('aria-readonly', 'false');
                    });
                });
            });
        });

        it('should not react to mutation events', function() {
            makeComponent({
                checkChangeBuffer: 0,
                readOnly: true,
                renderTo: Ext.getBody()
            });

            spyOn(component, 'checkChange');

            // Trigger a cross-browser field mutation event.
            jasmine.fireKeyEvent(component.inputEl.dom, 'keyup', 65);

            // The trick here is that we need to ensure that checkChange() isn't called for readOnly components.
            // Since it's called on a delayed task, we'll need to use waits() here, unfortunately.
            waits(10);

            runs(function() {
                expect(component.checkChange.callCount).toBe(0);
            });
        });
    });

    describe("emptyText", function() {
        // NOTE emptyText is handled via the HTML5 'placeholder' attribute for those browsers which
        // support it, and the old modified-value method for other browsers, so the tests differ.

        if (Ext.supports.Placeholder) {
            it("should set the input's placeholder attribute", function() {
                makeComponent({
                    emptyText: 'empty',
                    renderTo: Ext.getBody()
                });
                expect(component.inputEl.dom.placeholder).toBe('empty');
                expect(component.inputEl).toHaveCls(component.emptyCls);
            });

            it("should be able to use \" in the emptyText", function() {
                makeComponent({
                    emptyText: 'Please type "foo" here!',
                    renderTo: Ext.getBody()
                });

                expect(component.inputEl.dom.placeholder).toBe('Please type "foo" here!');
            });

            it("should be able to be added with setEmptyText", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                component.setEmptyText('Foo');
                expect(component.emptyText).toBe('Foo');
                expect(component.inputEl.dom.placeholder).toBe('Foo');
                expect(component.inputEl).toHaveCls(component.emptyCls);
            });

            it("should be able to be removed with setEmptyText", function() {
                makeComponent({
                    emptyText: 'Bar',
                    renderTo: Ext.getBody()
                });
                component.setEmptyText('');
                expect(component.emptyText).toBe('');
                expect(component.inputEl.dom.value).toBe('');
                expect(component.inputEl).toHaveCls(component.emptyCls);
            });

            describe("with initial value", function() {
                it("should be able to change the empty text", function() {
                    makeComponent({
                        emptyText: 'empty',
                        value: 'Foo',
                        renderTo: Ext.getBody()
                    });

                    expect(component.inputEl.dom.placeholder).toBe('empty');
                    component.setEmptyText('Bar');
                    expect(component.inputEl.dom.placeholder).toBe('Bar');
                });

                it("should add emptyCls when empty and remove it when not empty", function() {
                    makeComponent({
                        emptyText: 'empty',
                        value: 'Foo',
                        renderTo: Ext.getBody()
                    });

                    expect(component.inputEl).not.toHaveCls(component.emptyCls);
                    component.setValue();
                    expect(component.inputEl).toHaveCls(component.emptyCls);
                });
            });
        }
        else {
            describe("when the value is empty", function() {
                var label;

                beforeEach(function() {
                    makeComponent({
                        emptyText: 'empty',
                        renderTo: Ext.getBody()
                    });
                    label = component.placeholderLabel;
                });

                it("should set placeholder label text to the emptyText", function() {
                    expect(label.getHtml()).toBe('empty');
                    expect(component.inputEl.dom.value).toBe('');
                });

                it("should be able to use \" in the emptyText", function() {
                    component.destroy();

                    makeComponent({
                        emptyText: 'Please type "foo" here!',
                        renderTo: Ext.getBody()
                    });

                    expect(component.placeholderLabel.getHtml()).toBe('Please type "foo" here!');
                });

                it("should add the emptyCls to the inputEl", function() {
                    expect(component.inputEl.hasCls(component.emptyCls)).toBe(true);
                });

                it("should return empty string from the value getters and emptytext form getEmptyText", function() {
                    expect(component.getValue()).toBe('');
                    expect(component.getRawValue()).toBe('');
                    expect(component.getEmptyText()).toBe('empty');
                });
            });

            describe("when the value is not empty", function() {
                beforeEach(function() {
                    makeComponent({
                        emptyText: 'empty',
                        value: 'value',
                        renderTo: Ext.getBody()
                    });
                });

                it("should set the input field's value to the specified value", function() {
                    expect(component.inputEl.dom.value).toEqual('value');
                });

                it("should remove the emptyCls from the input element", function() {
                    expect(component.inputEl.hasCls(component.emptyCls)).toBe(false);
                });

                it("should return the value from the value getters", function() {
                    expect(component.getValue()).toEqual('value');
                    expect(component.getRawValue()).toEqual('value');
                });
            });

            describe("when the value is equal to the placeholder/emptyText", function() {
                beforeEach(function() {
                    makeComponent({
                        emptyText: 'value',
                        value: 'value',
                        renderTo: Ext.getBody()
                    });
                });

                it("should set the input field's value to the specified value", function() {
                    expect(component.inputEl.dom.value).toEqual('value');
                });

                it("should remove the emptyCls from the input element", function() {
                    expect(component.inputEl.hasCls(component.emptyCls)).toBe(false);
                });

                it("should return the value from the value getters", function() {
                    expect(component.getValue()).toEqual('value');
                    expect(component.getRawValue()).toEqual('value');
                });
            });

            describe("using setEmptyText", function() {
                describe("when value is empty", function() {
                    it("should be able to add empty text", function() {
                        makeComponent({
                            renderTo: Ext.getBody()
                        });

                        component.setEmptyText('Foo');
                        expect(component.emptyText).toBe('Foo');
                        expect(component.placeholderLabel.getHtml()).toBe('Foo');
                        expect(component.inputEl).toHaveCls(component.emptyCls);
                    });

                    it("should be able to remove empty text", function() {
                         makeComponent({
                            emptyText: 'Bar',
                            renderTo: Ext.getBody()
                        });

                        component.setEmptyText('');
                        expect(component.emptyText).toBe('');
                        expect(component.inputEl.dom.value).toBe('');
                        expect(component.inputEl).toHaveCls(component.emptyCls);
                    });

                });

                describe("when value is not empty", function() {
                    it("should be able to add empty text", function() {
                        makeComponent({
                            value: 'value',
                            renderTo: Ext.getBody()
                        });

                        component.setEmptyText('Foo');
                        expect(component.emptyText).toEqual('Foo');
                        expect(component.inputEl.dom.value).toEqual('value');
                        expect(component.inputEl).not.toHaveCls(component.emptyCls);
                        expect(component.getValue()).not.toBe('Foo');

                        component.setValue();

                        expect(component.placeholderLabel.getHtml()).toEqual('Foo');
                        expect(component.inputEl).toHaveCls(component.emptyCls);
                        expect(component.getValue()).not.toBe('Foo');
                        expect(component.inputEl.dom.value).toBe('');
                    });

                    it("should be able to remove empty text", function() {
                         makeComponent({
                            emptyText: 'Bar',
                            value: 'value',
                            renderTo: Ext.getBody()
                        });

                        expect(component.inputEl).not.toHaveCls(component.emptyCls);
                        component.setEmptyText('');
                        expect(component.emptyText).toBe('');
                        expect(component.inputEl.dom.value).toEqual('value');
                        expect(component.getValue()).toEqual('value');
                        component.setValue();
                        expect(component.inputEl.dom.value).toEqual('');
                        expect(component.inputEl).toHaveCls(component.emptyCls);
                    });
                });
            });
            // TODO check that the empty text is removed/added when focusing/blurring the field
        }
    });

    describe("validation", function() {
        describe("minLength", function() {
            it("should ignore minLength when allowBlank is set", function() {
                makeComponent({
                    minLength: 5,
                    allowBlank: true
                });
                expect(component.getErrors()).toEqual([]);
            });

            it("should have an error if the value is less than the minLength", function() {
                makeComponent({
                    minLength: 5,
                    allowBlank: false,
                    value: 'four'
                });
                expect(component.getErrors()).toContain("The minimum length for this field is 5");
            });

            it("should not have an error if the value length exceeds minLength", function() {
                makeComponent({
                    minLength: 5,
                    allowBlank: false,
                    value: "more than 5"
                });
                expect(component.getErrors()).toEqual([]);
            });
        });

        describe("maxLength", function() {
            it("should have an error if the value is more than the maxLength", function() {
                makeComponent({
                    maxLength: 5,
                    value: "more than 5"
                });
                expect(component.getErrors()).toContain("The maximum length for this field is 5");
            });

            it("should not have an error if the value length is less than the maxLength", function() {
                makeComponent({
                    maxLength: 5,
                    value: "foo"
                });
                expect(component.getErrors()).toEqual([]);
            });

            it("should set the maxlength attribute when enforceMaxLength is used", function() {
                makeComponent({
                    maxLength: 5,
                    enforceMaxLength: true,
                    renderTo: Ext.getBody()
                });
                expect(component.inputEl.dom.maxLength).toEqual(5);
            });

            it("should ignore enforceMaxLength if the max is the default", function() {
                makeComponent({
                    enforceMaxLength: true,
                    renderTo: Ext.getBody()
                });

                var dom = document.createElement('input'),
                    len;

                dom.type = 'text';
                len = dom.maxLength;
                dom = null;

                // In some browsers, even if the maxLength is not set
                // it still returns a numeric value
                expect(component.inputEl.dom.maxLength).toEqual(len);
            });
        });

        describe("allowBlank", function() {
            it("should have no errors if allowBlank is true and the field is empty", function() {
                makeComponent();
                expect(component.getErrors()).toEqual([]);
            });

            it("should have no errors if allowBlank is false and the field is not empty", function() {
                makeComponent({
                    allowBlank: false,
                    value: "not empty"
                });
                expect(component.getErrors()).toEqual([]);
            });

            it("should have an error if allowBlank is false and the field is empty", function() {
                makeComponent({
                    allowBlank: false
                });
                expect(component.getErrors()).toContain("This field is required");
            });

            it("should set allowBlank to false when using allowOnlyWhitespace: false", function() {
                makeComponent({
                    allowOnlyWhitespace: false
                });
                expect(component.allowBlank).toBe(false);
            });

            it("should not allow only whitespace when allowOnlyWhitespace: false", function() {
                makeComponent({
                    allowOnlyWhitespace: false,
                    value: '     '
                });
                expect(component.getErrors()).toContain('This field is required');
            });
        });

        describe("regex", function() {
            it("should have an error if the value doesn't match the regex", function() {
                makeComponent({
                    value: "bar",
                    regex: /foo/,
                    regexText: "regex error"
                });
                expect(component.getErrors()).toContain("regex error");
            });

            it("should not have an error if the value matches the regex", function() {
                makeComponent({
                    regex: /foo/,
                    regexText: "foo"
                });
                expect(component.getErrors()).toEqual([]);
            });
        });

        describe("validator", function() {
            it("should have an error if the value doesn't match the validator", function() {
                makeComponent({
                    allowBlank: false,
                    validator: function(value) {
                        return value === "foo" ? true : "error message";
                    },
                    value: "bar"
                });
                expect(component.getErrors()).toContain("error message");
            });

            it("should not have an error if the value matches the validator", function() {
                makeComponent({
                    allowBlank: false,
                    validator: function(value) {
                        return value === "foo" ? true : "error message";
                    },
                    value: "foo"
                });
                expect(component.getErrors()).toEqual([]);
            });
        });

        describe("aria-invalid", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    allowBlank: false,
                    value: "foo"
                });
            });

            it("should be false when valid", function() {
                expect(component).toHaveAttr('aria-invalid', 'false');
            });

            it("should be true when invalid", function() {
                component.setValue('');

                expect(component).toHaveAttr('aria-invalid', 'true');
            });

            it("should be false when invalid mark is cleared", function() {
                component.setValue('');
                component.setValue('bar');

                expect(component).toHaveAttr('aria-invalid', 'false');
            });
        });

        describe("invalidCls", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    allowBlank: false,
                    invalidCls: 'bar',
                    value: "foo"
                });
            });

            it("should add the invalidCls to the component element", function() {
                component.setValue('');
                expect(component.el).toHaveCls('bar');
            });

            it("should remove the invalidCls from the component element", function() {
                component.setValue('');
                expect(component.el).toHaveCls('bar');

                component.setValue('foo');
                expect(component.el).not.toHaveCls('bar');
            });
        });
    });

    describe("isDirty", function() {
        it("should return true when the value is different than the original value", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 'foo'
            });
            component.setValue('bar');
            expect(component.isDirty()).toBe(true);
        });

        it("should return false when the value is equal to the original value", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 'foo'
            });
            component.setValue('bar');
            component.setValue('foo');
            expect(component.isDirty()).toBe(false);
        });

        it("should fire the dirtychange event", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 'foo'
            });
            var spy = jasmine.createSpy();

            component.on('dirtychange', spy);

            component.setValue('bar');
            expect(spy.calls.length).toEqual(1);
            expect(spy.mostRecentCall.args).toEqual([component, true]);

            component.setValue('foo');
            expect(spy.calls.length).toEqual(2);
            expect(spy.mostRecentCall.args).toEqual([component, false]);
        });

        it("should add the dirtyCls to the main element", function() {
            makeComponent({
                renderTo: Ext.getBody(),
                value: 'foo',
                dirtyCls: 'dirrrrrty'
            });
            expect(component.el.hasCls('dirrrrrty')).toBe(false);
            component.setValue('bar');
            expect(component.el.hasCls('dirrrrrty')).toBe(true);
        });
    });

    describe("enableKeyEvents", function() {
        describe("enableKeyEvents=false", function() {
            beforeEach(function() {
                makeComponent({
                    enableKeyEvents: false,
                    renderTo: Ext.getBody()
                });
            });
            it("should not fire the keydown event", function() {
                var spy = jasmine.createSpy();

                component.on('keydown', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keydown');
                expect(spy).not.toHaveBeenCalled();
            });
            it("should not fire the keypress event", function() {
                var spy = jasmine.createSpy();

                component.on('keypress', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keypress');
                expect(spy).not.toHaveBeenCalled();
            });
            it("should not fire the keyup event", function() {
                var spy = jasmine.createSpy();

                component.on('keyup', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup');
                expect(spy).not.toHaveBeenCalled();
            });
        });
        describe("enableKeyEvents=true", function() {
            beforeEach(function() {
                makeComponent({
                    enableKeyEvents: true,
                    renderTo: Ext.getBody()
                });
            });
            it("should not fire the keydown event", function() {
                var spy = jasmine.createSpy();

                component.on('keydown', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keydown');
                expect(spy).toHaveBeenCalled();
            });
            it("should not fire the keypress event", function() {
                var spy = jasmine.createSpy();

                component.on('keypress', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keypress');
                expect(spy).toHaveBeenCalled();
            });
            it("should not fire the keyup event", function() {
                var spy = jasmine.createSpy();

                component.on('keyup', spy);
                jasmine.fireKeyEvent(component.inputEl.dom, 'keyup');
                expect(spy).toHaveBeenCalled();
            });
        });
    });

    describe("disable/enable", function() {
        describe("disabled config", function() {
            beforeEach(function() {
                makeComponent({
                    disabled: true,
                    renderTo: Ext.getBody()
                });
            });

            it("should set the input element's disabled property to true", function() {
                expect(component.inputEl.dom.disabled).toBe(true);
            });

            it("should set aria-disabled to true", function() {
                expect(component).toHaveAttr('aria-disabled', 'true');
            });

            if (Ext.isIE) {
                it("should set the input element's unselectable property to 'on'", function() {
                    expect(component.inputEl.dom.unselectable).toEqual('on');
                });
            }
        });

        describe("disable method", function() {
            beforeEach(function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                component.disable();
            });

            it("should set the input element's disabled property to true", function() {
                expect(component.inputEl.dom.disabled).toBe(true);
            });

            it("should set aria-disabled to true", function() {
                expect(component).toHaveAttr('aria-disabled', 'true');
            });

            if (Ext.isIE) {
                it("should set the input element's unselectable property to 'on'", function() {
                    expect(component.inputEl.dom.unselectable).toEqual('on');
                });
            }
        });

        describe("enable method", function() {
            beforeEach(function() {
                makeComponent({
                    disabled: true,
                    renderTo: Ext.getBody()
                });
                component.enable();
            });

            it("should set the input element's disabled property to false", function() {
                expect(component.inputEl.dom.disabled).toBe(false);
            });

            it("should set aria-disabled to false", function() {
                expect(component).toHaveAttr('aria-disabled', 'false');
            });

            if (Ext.isIE) {
                it("should set the input element's unselectable property to ''", function() {
                    expect(component.inputEl.dom.unselectable).toEqual('');
                });
            }
        });
    });

    describe("maskRe", function() {
        // TODO need a good way to test the cancellation of keypress events for masked chars
    });

    describe("stripCharsRe", function() {
        beforeEach(function() {
            makeComponent({
                stripCharsRe: /[B9]/gi,
                renderTo: Ext.getBody()
            });
            component.setRawValue('ab9 cB9d');
        });

        it("should remove characters matching the RE from the value that is returned", function() {
            expect(component.getValue()).toEqual('a cd');
        });

        it("should remove all occurences that match RE from the value that is returned without specifying the global flag", function() {
            component.destroy();
            makeComponent({
                stripCharsRe: /[B9]/i,
                renderTo: Ext.getBody()
            });
            component.setRawValue('TB9hib9s iB9s testing tB9he mB9aB9sk witB9hb9ouB9tb9 tb9hb9e gB9 fB9B9lab9g');
            expect(component.getValue()).toBe('This is testing the mask without the g flag');
        });

        it("should update the raw field value with the stripped value", function() {
            expect(component.inputEl.dom.value).toEqual('ab9 cB9d');
            component.getValue();
            expect(component.inputEl.dom.value).toEqual('a cd');
        });
    });

    describe("selectText method", function() {
        // utility to get the begin and end of the selection range across browsers
        function getSelectedText() {
            var selection = component.getTextSelection();

            return component.inputEl.dom.value.substring(selection[0], selection[1]);
        }

        beforeEach(function() {
            makeComponent({ renderTo: Ext.getBody() });
        });

        it("should select the entire value by default", function() {
            component.setValue('field value');
            component.selectText();

            if (Ext.isIE) {
                waits(10);
            }

            runs(function() {
                expect(getSelectedText()).toEqual('field value');
            });
        });
        it("should select from the 'start' argument", function() {
            component.setValue('field value');
            component.selectText(3);

            if (Ext.isIE) {
                waits(10);
            }

            runs(function() {
                expect(getSelectedText()).toEqual('ld value');
            });
        });
        it("should select to the 'end' argument", function() {
            component.setValue('field value');
            component.selectText(3, 8);

            if (Ext.isIE) {
                waits(10);
            }

            runs(function() {
                expect(getSelectedText()).toEqual('ld va');
            });
        });
    });

    describe("autoSize method and = configs", function() {
        describe("with an auto width", function() {
            beforeEach(function() {
                makeComponent({
                    grow: true,
                    growMin: 30,
                    growMax: 100,
                    renderTo: Ext.getBody()
                });
            });

            it("should auto height with an initial value", function() {
                component.destroy();
                makeComponent({
                    grow: true,
                    growMin: 10,
                    growMax: 300,
                    renderTo: Ext.getBody(),
                    value: 'abcdefghijk'
                });
                expect(component.getWidth()).toBeLessThan(300);
                expect(component.getWidth()).toBeGreaterThan(10);
            });

            it("should set the initial width to growMin", function() {
                expect(component.getWidth()).toBe(30);
            });

            it("should increase the width of the input as the value becomes longer", function() {
                component.setValue('value A');
                var width1 = component.getWidth();

                component.setValue('value AB');
                var width2 = component.getWidth();

                expect(width2).toBeGreaterThan(width1);
            });

            it("should decrease the width of the input as the value becomes shorter", function() {
                component.setValue('value AB');
                var width1 = component.getWidth();

                component.setValue('value A');
                var width2 = component.getWidth();

                expect(width2).toBeLessThan(width1);
            });

            it("should not increase the width above the growMax config", function() {
                component.setValue('a really long value that would go above the growMax config');
                var width = component.getWidth();

                expect(width).toBe(100);
            });

            it("should not decrease the width below the growMin config", function() {
                component.setValue('.');
                var width = component.getWidth();

                expect(width).toBe(30);
            });

            it("should work with markup", function() {
                component.setValue('<fake tag appears here');
                expect(component.getWidth()).toBeGreaterThan(30);
            });
        });

        describe("with a fixed width", function() {
            it("should have no affect on a configured wdith", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    grow: true,
                    growMin: 50,
                    width: 150,
                    growMax: 600
                });
                component.setValue('abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz');
                expect(component.getWidth()).toBe(150);
            });

            it("should have no affect on a calculated height", function() {
                makeComponent({
                    grow: true,
                    growMin: 100,
                    growMax: 700,
                    flex: 1
                });

                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    layout: 'hbox',
                    width: 150,
                    height: 150,
                    items: component
                });

                component.setValue('abcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz');
                expect(component.getWidth()).toBe(150);
                ct.destroy();
            });
        });
    });

    describe('fieldStyle', function() {
        function isRed(color) {
            if (color !== 'red red red red' && color !== '#ff0000' && color !== 'rgb(255, 0, 0)') {
                expect(color).toBe('red');
            }
        }

        it("should set the style of the inputEl when rendered", function() {
            makeComponent({
                fieldStyle: 'border-left-color:red;',
                renderTo: Ext.getBody()
            });
            var borderColor = component.inputEl.getStyle('border-left-color');

            isRed(borderColor);
        });
        describe('setFieldStyle method', function() {
            it("should apply the argument as the style of the rendered inputEl", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                component.setFieldStyle('border-left-color:red;');
                var borderColor = component.inputEl.getStyle('border-left-color');

                isRed(borderColor);
            });

            it("should store the argument as the fieldStyle and apply it when rendered", function() {
                makeComponent({});
                component.setFieldStyle('border-left-color:red;');
                component.render(Ext.getBody());
                var borderColor = component.inputEl.getStyle('border-left-color');

                isRed(borderColor);
            });
        });
    });

    describe('label hiding', function() {
        describe('hideEmptyLabel', function() {
            it("should render a label when fieldLabel is empty", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    hideEmptyLabel: true
                });
                expect(component.labelEl).not.toBeNull();
            });

            it("should render an empty label when set to false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    hideEmptyLabel: false
                });
                expect(component.labelEl).not.toBeNull();
            });
        });

        describe('hideLabel', function() {
            it("should render a label when true", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    hideLabel: true,
                    fieldLabel: 'foo'
                });
                expect(component.labelEl).not.toBeNull();
            });

            it("should render a label when false", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    hideLabel: false,
                    fieldLabel: 'foo'
                });
                expect(component.labelEl).not.toBeNull();
            });
        });
    });

    describe("setRawValue", function() {
        it("should not fire the change event when called", function() {
            var called = false;

            runs(function() {
                makeComponent();
                render();
                component.checkChangeBuffer = 0;
                component.on('change', function() {
                    called = true;
                });
                component.setRawValue('foo');
            });
            waits(50);
            runs(function() {
                expect(called).toBe(false);
            });
        });
    });

    describe('getSubmitData', function() {
        it("should return the field's value", function() {
            makeComponent({ name: 'myname', value: 'myvalue' });
            expect(component.getSubmitData()).toEqual({ myname: 'myvalue' });
        });
        it("should return empty string for an empty value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getSubmitData()).toEqual({ myname: '' });
        });
    });

    describe('getModelData', function() {
        it("should return the field's value", function() {
            makeComponent({ name: 'myname', value: 'myvalue' });
            expect(component.getModelData()).toEqual({ myname: 'myvalue' });
        });
        it("should return empty string for an empty value", function() {
            makeComponent({ name: 'myname', value: '' });
            expect(component.getModelData()).toEqual({ myname: '' });
        });
    });

    describe('binding', function() {
        var panel;

        afterEach(function() {
            panel = Ext.destroy(panel);
        });

        describe('fields to data model', function() {
            var User,
                session, viewModel, scheduler;

            function completeRequest(data) {
                Ext.Ajax.mockComplete({
                    status: 200,
                    responseText: Ext.encode(data)
                });
            }

            beforeEach(function() {
                MockAjaxManager.addMethods();

                session = new Ext.data.Session({
                    scheduler: {
                        // Make a huge tickDelay, we'll control it by forcing ticks
                        tickDelay: 1000000
                    }
                });

                Ext.data.Model.schema.setNamespace('spec');
                User = Ext.define('spec.User', {
                    extend: Ext.data.Model,

                    // W/o convert:null here the defaultValue kicks in and we get empty
                    // strings. For this test we don't want that.
                    fields: [
                        { name: 'first',       type: 'string', convert: null },
                        { name: 'last',        type: 'string', convert: null },
                        { name: 'email',       type: 'string', convert: null },
                        { name: 'formatField', type: 'string', convert: null },
                        { name: 'phone',       type: 'string', convert: null },
                        { name: 'color',       type: 'string', convert: null },
                        { name: 'description', type: 'string', convert: null },
                        { name: 'initial',     type: 'string', convert: null }
                    ],

                    validators: {
                        last: { type: 'length', min: 1 },
                        description: { type: 'length', min: 10, max: 200 },
                        color: { type: 'inclusion', list: [ 'red', 'white', 'blue' ] },
                        first: { type: 'exclusion', list: [ 'Ed' ] },
                        formatField: { type: 'format', matcher: /123/ },
                        email: 'email',
                        phone: { type: 'presence', message: 'Phone number required' },
                        initial: { type: 'length', min: 1 }
                    },

                    doValidate: function() {
                        //
                    }
                });

                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    modelValidation: true,
                    viewModel: {
                        id: 'rootVM',
                        session: session
                    },
                    defaults: {
                        xtype: 'textfield'
                    },
                    items: [{
                        itemId: 'description',
                        bind: '{theUser.description}'
                    }, {
                        itemId: 'last',
                        bind: '{theUser.last}'
                    }, {
                        itemId: 'formatField',
                        bind: '{theUser.formatField}'
                    }, {
                        itemId: 'color',
                        bind: '{theUser.color}'
                    }, {
                        itemId: 'first',
                        bind: '{theUser.first}'
                    }, {
                        itemId: 'email',
                        bind: '{theUser.email}'
                    }, {
                        itemId: 'phone',
                        bind: '{theUser.phone}'
                    }, {
                        itemId: 'initial',
                        bind: '{theUser.initial}'
                    }, {
                        itemId: 'extraStuff',
                        bind: '{theUser.extraStuff}'
                    }]
                });

                viewModel = panel.getViewModel();
                scheduler = viewModel.getScheduler();
                viewModel.linkTo('theUser', {
                    reference: 'User',
                    id: 42
                });
            });

            afterEach(function() {
                Ext.undefine('spec.User');
                Ext.destroy(viewModel, session);

                session = scheduler = viewModel = null;

                expect(Ext.util.Scheduler.instances.length).toBe(0);

                MockAjaxManager.removeMethods();
                Ext.data.Model.schema.clear(true);
            });

            describe("delivering validation messages", function() {
                beforeEach(function() {
                    completeRequest({
                        id: 42,
                        description: 'too short',
                        color: 'not a valid color',
                        first: 'Ed',
                        formatField: 'abc',
                        email: 'abc',
                        initial: 'X',
                        extraStuff: 42
                    });
                });

                describe("for invalid fields", function() {
                    var V = Ext.data.validator;

                    function getMessage(T) {
                        return T.prototype.config.message;
                    }

                    it('should report description too short', function() {
                        var item = panel.child('#description');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('Length must be between 10 and 200');

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('description', '1234567890'); // long enough

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it('should report missing last name', function() {
                        var item = panel.child('#last');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('Length must be at least 1');

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('last', 'Spencer'); // present

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it("should have the correct bad format message", function() {
                        var item = panel.child('#formatField');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe(getMessage(V.Format));

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('formatField', '123'); // matches /123/

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it("should have the correct non-inclusion message", function() {
                        var item = panel.child('#color');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe(getMessage(V.Inclusion));

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('color', 'red'); // in the color list

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it("should have the correct non-exclusion message", function() {
                        var item = panel.child('#first');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe(getMessage(V.Exclusion));

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('first', 'Edward'); // not excluded

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it("should have the correct bad email format message", function() {
                        var item = panel.child('#email');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe(getMessage(V.Email));

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('email', 'ed@sencha.com'); // a valid email

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });

                    it("should allow user-defined error messages", function() {
                        var item = panel.child('#phone');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('Phone number required');

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('phone', '555-1212'); // present

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(0);
                    });
                }); // for invalid fields

                describe('for valid fields', function() {
                    it('should report initial as valid', function() {
                        var item = panel.child('#initial');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(0);

                        // Now make the field valid and see if our binding is notified.
                        var rec = session.getRecord('User', 42);

                        rec.set('initial', ''); // too short now

                        scheduler.notify();

                        errors = item.getErrors();

                        expect(scheduler.passes).toBe(2);
                        expect(errors.length).toBe(1);
                        expect(errors[0]).toBe('Length must be at least 1');
                    });
                });

                describe('for undeclared fields', function() {
                    it('should report extraStuff as undefined', function() {
                        var item = panel.child('#extraStuff');

                        scheduler.notify();

                        var errors = item.getErrors();

                        expect(scheduler.passes).toBe(1);
                        expect(errors.length).toBe(0);
                    });
                });
            }); // delivering validation messages
        });

        describe('use cases', function() {
            it('should bind value of field to panel title', function() {
                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    viewModel: {
                        formulas: {
                            bar: function(get) {
                                return 'Brave Sir ' + get('foo');
                            }
                        }
                    },
                    referenceHolder: true,
                    defaultListenerScope: true,

                    wow: function(value) {
                        return value + '!!';
                    },

                    items: [{
                        xtype: 'panel',
                        reference: 'subPanel',
                        bind: {
                            title: 'Hello {bar:this.wow}!'
                        },
                        items: [{
                            xtype: 'textfield',
                            reference: 'fld',
                            bind: '{foo}'
                        }]
                    }]
                });

                var viewModel = panel.getViewModel(),
                    subPanel = panel.lookupReference('subPanel'),
                    fld = panel.lookupReference('fld');

                fld.setValue('Robin');
                viewModel.getScheduler().notify();

                expect(subPanel.title).toBe('Hello Brave Sir Robin!!!');
            });

            it('should be disabled by binding to a checkbox checked state', function() {
                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    viewModel: true,
                    referenceHolder: true,

                    items: [{
                        xtype: 'checkbox',
                        reference: 'chk'
                    }, {
                        xtype: 'textfield',
                        reference: 'textfld',
                        bind: {
                            disabled: '{!chk.checked}' // notice the "!" here
                        }
                    }]
                });

                var chk = panel.lookupReference('chk');

                var textFld = panel.lookupReference('textfld');

                var viewModel = panel.getViewModel();

                var scheduler = viewModel.getScheduler();

                scheduler.notify(); // run the bindings
                expect(textFld.disabled).toBe(true);
                expect(scheduler.passes).toBe(1);

                chk.setValue(true);

                scheduler.notify(); // run the bindings
                expect(textFld.disabled).toBe(false);
                expect(scheduler.passes).toBe(2);
            });

            it('should be disabled by binding to a button pressed state', function() {
                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    viewModel: true,
                    referenceHolder: true,

                    items: [{
                        xtype: 'button',
                        reference: 'btn',
                        enableToggle: true,
                        // this is here to ensure that instance config does not break
                        // the class publishes
                        publishes: [ 'disabled' ]
                    }, {
                        xtype: 'textfield',
                        reference: 'textfld',
                        bind: {
                            disabled: '{btn.pressed}'
                        }
                    }]
                });

                var btn = panel.lookupReference('btn');

                var textFld = panel.lookupReference('textfld');

                var viewModel = panel.getViewModel();

                var scheduler = viewModel.getScheduler();

                scheduler.notify(); // run the bindings
                expect(textFld.disabled).toBe(false);
                expect(scheduler.passes).toBe(1);

                btn.setPressed();

                scheduler.notify(); // run the bindings
                expect(textFld.disabled).toBe(true);
                expect(scheduler.passes).toBe(2);
            });

            it('should be able to publish its value for others to use', function() {
                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    viewModel: true,
                    referenceHolder: true,

                    items: [{
                        xtype: 'textfield',
                        reference: 'textfld',
                        publishes: [ 'value' ]
                    }, {
                        xtype: 'displayfield',
                        reference: 'display',
                        bind: 'Hello {textfld.value}!'
                    }]
                });

                var display = panel.lookupReference('display'),
                    textFld = panel.lookupReference('textfld'),
                    viewModel = panel.getViewModel(),
                    scheduler = viewModel.getScheduler();

                scheduler.notify(); // run the bindings
                var value = display.getValue();

                expect(value).toBe('Hello !');
                expect(scheduler.passes).toBe(1);

                textFld.setValue('World');

                scheduler.notify(); // run the bindings
                value = display.getValue();
                expect(value).toBe('Hello World!');
                expect(scheduler.passes).toBe(2);
            });

            it('should be able to publish value, rawValue and dirty ', function() {
                panel = Ext.widget({
                    xtype: 'panel',
                    renderTo: Ext.getBody(),
                    viewModel: true,
                    referenceHolder: true,

                    items: [{
                        xtype: 'textfield',
                        reference: 'txt',
                        publishes: [ 'value', 'rawValue', 'dirty' ]
                    }, {
                        xtype: 'displayfield',
                        reference: 'display',
                        bind: 'R: {txt.rawValue} / V: {txt.value} / D: {!txt.dirty}'
                    }]
                });

                var display = panel.lookupReference('display'),
                    textFld = panel.lookupReference('txt'),
                    viewModel = panel.getViewModel(),
                    scheduler = viewModel.getScheduler();

                scheduler.notify(); // run the bindings
                var value = display.getValue();

                expect(value).toBe('R:  / V:  / D: true');
                expect(scheduler.passes).toBe(1);

                textFld.setValue('World');

                scheduler.notify(); // run the bindings
                value = display.getValue();
                expect(value).toBe('R: World / V: World / D: false');
                expect(scheduler.passes).toBe(2);
            });
        }); // use cases
    }); // binding

    describe('triggers', function() {
        var fooHandler = jasmine.createSpy(),
            barHandler = jasmine.createSpy(),
            fakeScope = {},
            fooTrigger, barTrigger, fooEl, barEl;

        function create(cfg) {
            component = Ext.widget(Ext.merge({
                xtype: 'textfield',
                renderTo: document.body,
                triggers: {
                    foo: {
                        cls: 'foo-trigger',
                        handler: fooHandler,
                        tooltip: 'foobaroo'
                    },
                    bar: {
                        cls: 'bar-trigger',
                        handler: barHandler,
                        scope: fakeScope
                    }
                }
            }, cfg));

            fooTrigger = component.getTrigger('foo');
            barTrigger = component.getTrigger('bar');
            fooEl = fooTrigger.getEl();
            barEl = barTrigger.getEl();
        }

        it("should create Trigger instances", function() {
            create();
            expect(fooTrigger instanceof Ext.form.trigger.Trigger).toBe(true);
            expect(barTrigger instanceof Ext.form.trigger.Trigger).toBe(true);
        });

        it("should render triggers", function() {
            create();
            expect(component.triggerWrap.selectNode('.foo-trigger', false)).toBe(fooEl);
            expect(component.triggerWrap.selectNode('.bar-trigger', false)).toBe(barEl);
        });

        it("should render data-qtip attribute for tooltips", function() {
            create();

            expect(fooEl).toHaveAttr('data-qtip', 'foobaroo');
            expect(barEl).not.toHaveAttr('data-qtip');
        });

        it("should allow setting tooltip dynamically", function() {
            create();

            barTrigger.setTooltip('blergofumble');

            expect(fooEl).toHaveAttr('data-qtip', 'foobaroo');
            expect(barEl).toHaveAttr('data-qtip', 'blergofumble');
        });

        it("should allow changing tooltip dynamically", function() {
            create();

            fooTrigger.setTooltip('zombo gurgle!');

            expect(fooEl).toHaveAttr('data-qtip', 'zombo gurgle!');
            expect(barEl).not.toHaveAttr('data-qtip');
        });

        it("should call trigger handlers", function() {
            var args;

            create();

            jasmine.fireMouseEvent(fooEl, 'click');
            args = fooHandler.mostRecentCall.args;
            expect(args[0]).toBe(component);
            expect(args[1]).toBe(fooTrigger);
            expect(args[2] instanceof Ext.event.Event).toBe(true);
            expect(fooHandler.mostRecentCall.object).toBe(component);

            jasmine.fireMouseEvent(barEl, 'click');
            args = barHandler.mostRecentCall.args;
            expect(args[0]).toBe(component);
            expect(args[1]).toBe(barTrigger);
            expect(args[2] instanceof Ext.event.Event).toBe(true);
            // TODO: scope doesn't work due to config system forking the original object
//            expect(barHandler.mostRecentCall.object).toBe(fakeScope);
        });

        it("should create a triggerEl composite element for 4.x compat", function() {
            create();
            expect(component.triggerEl instanceof Ext.CompositeElement).toBe(true);
            expect(component.triggerEl.elements[0]).toBe(fooEl);
            expect(component.triggerEl.elements[1]).toBe(barEl);
        });

        it("should create a triggerCell composite element for 4.x compat", function() {
            create();
            expect(component.triggerCell).toBe(component.triggerEl);
        });

        it("should order the triggers by weight", function() {
            create({
                triggers: {
                    foo: {
                        weight: 1
                    }
                }
            });

            expect(barEl.next()).toBe(fooEl);
        });

        it("should hide triggers on render if hideTrigger is true", function() {
            create({
                hideTrigger: true
            });

            expect(fooTrigger.hidden).toBe(true);
            expect(barTrigger.hidden).toBe(true);
            expect(fooEl.isStyle('display', 'none')).toBe(true);
            expect(barEl.isStyle('display', 'none')).toBe(true);
        });

        it("should hide/show all triggers after render using setHideTrigger", function() {
            create();

            component.setHideTrigger(true);

            expect(fooTrigger.hidden).toBe(true);
            expect(barTrigger.hidden).toBe(true);
            expect(fooEl.isStyle('display', 'none')).toBe(true);
            expect(barEl.isStyle('display', 'none')).toBe(true);

            component.setHideTrigger(false);

            expect(fooTrigger.hidden).toBe(false);
            expect(barTrigger.hidden).toBe(false);
            expect(fooEl.isStyle('display', 'none')).toBe(false);
            expect(barEl.isStyle('display', 'none')).toBe(false);
        });
    });

    describe("grow", function() {
        beforeEach(function() {
            Ext.util.CSS.createStyleSheet(
                // make the input el have a 9px character width
                '.x-form-text { font:15px monospace;letter-spacing:0px; }',
                'growStyleSheet'
            );
        });

        afterEach(function() {
            Ext.util.CSS.removeStyleSheet('growStyleSheet');
        });

        function getExpectedWidth() {
            var inputEl = component.inputEl,
                textMeasure = inputEl.getTextWidth(inputEl.dom.value),
                borders = component.inputWrap.getBorderWidth('lr') + component.triggerWrap.getBorderWidth('lr'),
                inputElPadding = inputEl.getPadding('lr'),
                triggerWidth = 0;

            Ext.Object.each(component.getTriggers(), function(key, trigger) {
                triggerWidth += trigger.el.getWidth();
            });

            return textMeasure + borders + triggerWidth + inputElPadding;
        }

        it("should start out at growMin", function() {
            makeComponent({
                renderTo: document.body,
                grow: true,
                growMin: 50
            });

            expect(component.getWidth()).toBe(50);
        });

        it("should initially render at the width of the text", function() {
            makeComponent({
                renderTo: document.body,
                value: 'mmmmmmmmmm',
                grow: true,
                growMin: 50
            });

            expect(component.getWidth()).toBe(getExpectedWidth());
        });

        it("should initially render with a width of growMax if initial text width exceeds growMax", function() {
            makeComponent({
                renderTo: document.body,
                value: 'mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm',
                grow: true,
                growMax: 200
            });

            expect(component.getWidth()).toBe(200);
        });

        it("should grow and shrink", function() {
            makeComponent({
                renderTo: document.body,
                grow: true,
                triggers: {
                    foo: {}
                },
                growMin: 100,
                growMax: 200
            });

            expect(component.getWidth()).toBe(100);

            component.setValue('mmmmmmmmmmmmmm');

            expect(component.getWidth()).toBe(getExpectedWidth());

            component.setValue('mmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmmm');

            expect(component.getWidth()).toBe(200);

            component.setValue('mmmmmmmmmmmmmm');

            expect(component.getWidth()).toBe(getExpectedWidth());

            component.setValue('m');

            expect(component.getWidth()).toBe(100);
        });
    });

    describe('layout', function() {
        var dimensions = {
            1: 'width',
            2: 'height',
            3: 'width and height'
        };

        function makeLayoutSuite(shrinkWrap, autoFitErrors) {
            describe((shrinkWrap ? ("shrink wrap " + dimensions[shrinkWrap]) : "fixed width and height") +
                " autoFitErrors: " + autoFitErrors, function() {
                var shrinkWidth = (shrinkWrap & 1),
                    shrinkHeight = (shrinkWrap & 2),
                    errorWidth = 18, // the width of the error when side aligned
                    errorHeight = 20, // the height of the error when bottom aligned
                    errorIconSize = 16, // the size of the error icon element
                    errorIconMargin = 1, // the left margin of the error icon element
                    labelWidth = 105, // the width of the label when side aligned
                    labelPadding = 5, // right padding of the label when side aligned
                    labelInnerY = [3, 4], // the y offset of the inner label element when side aligned
                    labelInnerWidth = labelWidth - labelPadding, // the width of the inner label element when side aligned
                    borderWidth = 1, // the width of the textarea border
                    bodyWidth = 150, // the width of the bodyEl
                    bodyHeight = shrinkHeight ? 22 : 100, // the height of the bodyEl
                    labelHeight = 23, // the height of the label when top aligned
                    hideLabel, topLabel,  width, height;

                function create(cfg) {
                    cfg = cfg || {};

                    hideLabel = cfg.hideLabel;
                    topLabel = (cfg.labelAlign === 'top');
                    width = bodyWidth;
                    height = bodyHeight;

                    if (!hideLabel && !topLabel) {
                        width += labelWidth;
                    }

                    if (!hideLabel && topLabel) {
                        height += labelHeight;
                    }

                    if (cfg.msgTarget === 'side') {
                        width += errorWidth;
                    }

                    if (cfg.msgTarget === 'under') {
                        height += errorHeight;
                    }

                    component = Ext.create('Ext.form.field.Text', Ext.apply({
                        renderTo: document.body,
                        height: shrinkHeight ? null : height,
                        width: shrinkWidth ? null : width,
                        autoFitErrors: autoFitErrors,
                        // use a fixed size element vs. text for the field label for
                        // consistency of measurement cross-browser
                        fieldLabel: '<span style="display:inline-block;width:' + labelInnerWidth +
                            'px;background-color:red;box-sizing:border-box;">&nbsp;</span>',
                        labelSeparator: ''
                    }, cfg));
                }

                function setError(msg) {
                    component.setActiveError(msg || "Error Message");
                }

                // makes a suite for side labels (labelAlign: 'left' or labelAlign: 'right')
                // The specs contained herein should produce identical results for left
                // and right alignment, with the exception of the text align of the
                // label's inner element.
                function makeSideLabelSuite(labelAlign) {
                    describe(labelAlign + " label", function() {
                        var leftLabel = (labelAlign === 'left');

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout", function() {
                            create({
                                labelAlign: labelAlign
                            });

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    x: 0,
                                    y: 0,
                                    w: labelWidth,
                                    h: height
                                },
                                '.x-form-item-label-inner': {
                                    x: leftLabel ? 0 : labelWidth - labelPadding - labelInnerWidth,
                                    y: labelInnerY,
                                    w: labelInnerWidth
                                },
                                bodyEl: {
                                    x: labelWidth,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: labelWidth + borderWidth,
                                    y: borderWidth,
                                    w: bodyWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                }
                            });
                            expect(component.errorWrapEl).toBeNull();
                        });

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout with side error", function() {
                            create({
                                labelAlign: labelAlign,
                                msgTarget: 'side'
                            });

                            setError();

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    x: 0,
                                    y: 0,
                                    w: labelWidth,
                                    h: height
                                },
                                '.x-form-item-label-inner': {
                                    x: leftLabel ? 0 : labelWidth - labelPadding - labelInnerWidth,
                                    y: labelInnerY,
                                    w: labelInnerWidth
                                },
                                bodyEl: {
                                    x: labelWidth,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: labelWidth + borderWidth,
                                    y: borderWidth,
                                    w: bodyWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: width - errorWidth,
                                    y: 0,
                                    w: errorWidth,
                                    h: height
                                },
                                errorEl: {
                                    x: width - errorWidth + errorIconMargin,
                                    y: (bodyHeight - errorIconSize) / 2,
                                    w: errorIconSize,
                                    h: errorIconSize
                                }
                            });
                        });

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout with hidden side error", function() {
                            create({
                                labelAlign: labelAlign,
                                msgTarget: 'side'
                            });

                            var bdWidth = (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth;

                            expect(component).toHaveLayout({
                                el: {
                                    w: (shrinkWidth && autoFitErrors) ? width - errorWidth : width,
                                    h: height
                                },
                                labelEl: {
                                    x: 0,
                                    y: 0,
                                    w: labelWidth,
                                    h: height
                                },
                                '.x-form-item-label-inner': {
                                    x: leftLabel ? 0 : labelWidth - labelPadding - labelInnerWidth,
                                    y: labelInnerY,
                                    w: labelInnerWidth
                                },
                                bodyEl: {
                                    x: labelWidth,
                                    y: 0,
                                    w: bdWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: labelWidth + borderWidth,
                                    y: borderWidth,
                                    w: bdWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: autoFitErrors ? 0 : width - errorWidth,
                                    y: autoFitErrors ? 0 : 0,
                                    w: autoFitErrors ? 0 : errorWidth,
                                    h: autoFitErrors ? 0 : height
                                },
                                errorEl: {
                                    x: autoFitErrors ? 0 : width - errorWidth + errorIconMargin,
                                    y: autoFitErrors ? 0 : (bodyHeight - errorIconSize) / 2,
                                    w: autoFitErrors ? 0 : errorIconSize,
                                    h: autoFitErrors ? 0 : errorIconSize
                                }
                            });
                        });

                        // TODO: EXTJS-12634
                        (Ext.isIE10m && !shrinkHeight ? xit : it)("should layout with under error", function() {
                            create({
                                labelAlign: labelAlign,
                                msgTarget: 'under'
                            });

                            setError();

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    x: 0,
                                    y: 0,
                                    w: labelWidth,
                                    h: bodyHeight
                                },
                                '.x-form-item-label-inner': {
                                    x: leftLabel ? 0 : labelWidth - labelPadding - labelInnerWidth,
                                    y: labelInnerY,
                                    w: labelInnerWidth
                                },
                                bodyEl: {
                                    x: labelWidth,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: labelWidth + borderWidth,
                                    y: borderWidth,
                                    w: bodyWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: 0,
                                    y: bodyHeight,
                                    w: width,
                                    h: errorHeight
                                },
                                errorEl: {
                                    x: labelWidth,
                                    y: bodyHeight,
                                    w: bodyWidth,
                                    h: errorHeight
                                }
                            });
                        });

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout with hidden label", function() {
                            create({
                                labelAlign: labelAlign,
                                hideLabel: true
                            });

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    xywh: '0 0 0 0'
                                },
                                bodyEl: {
                                    x: 0,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                }
                            });
                            expect(component.errorWrapEl).toBeNull();
                        });

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout with hidden label and side error", function() {
                            create({
                                labelAlign: labelAlign,
                                hideLabel: true,
                                msgTarget: 'side'
                            });

                            setError();

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    xywh: '0 0 0 0'
                                },
                                bodyEl: {
                                    x: 0,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: borderWidth,
                                    y: borderWidth,
                                    w: bodyWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: bodyWidth,
                                    y: 0,
                                    w: errorWidth,
                                    h: height
                                },
                                errorEl: {
                                    x: bodyWidth + errorIconMargin,
                                    y: (bodyHeight - errorIconSize) / 2,
                                    w: errorIconSize,
                                    h: errorIconSize
                                }
                            });
                        });

                        // https://sencha.jira.com/browse/EXTJS-12634
                        (Ext.isIE8 ? xit : it)("should layout with hidden label and hidden side error", function() {
                            create({
                                labelAlign: labelAlign,
                                hideLabel: true,
                                msgTarget: 'side'
                            });

                            var bdWidth = (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth;

                            expect(component).toHaveLayout({
                                el: {
                                    w: (shrinkWidth && autoFitErrors) ? width - errorWidth : width,
                                    h: height
                                },
                                labelEl: {
                                    xywh: '0 0 0 0'
                                },
                                bodyEl: {
                                    x: 0,
                                    y: 0,
                                    w: bdWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: borderWidth,
                                    y: borderWidth,
                                    w: bdWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: autoFitErrors ? 0 : bodyWidth,
                                    y: autoFitErrors ? 0 : 0,
                                    w: autoFitErrors ? 0 : errorWidth,
                                    h: autoFitErrors ? 0 : height
                                },
                                errorEl: {
                                    x: autoFitErrors ? 0 : bodyWidth + errorIconMargin,
                                    y: autoFitErrors ? 0 : (bodyHeight - errorIconSize) / 2,
                                    w: autoFitErrors ? 0 : errorIconSize,
                                    h: autoFitErrors ? 0 : errorIconSize
                                }
                            });
                        });

                        // TODO: EXTJS-12634
                        (Ext.isIE10m && !shrinkHeight ? xit : it)("should layout with hidden label and under error", function() {
                            create({
                                labelAlign: labelAlign,
                                hideLabel: true,
                                msgTarget: 'under'
                            });

                            setError();

                            expect(component).toHaveLayout({
                                el: {
                                    w: width,
                                    h: height
                                },
                                labelEl: {
                                    xywh: '0 0 0 0'
                                },
                                bodyEl: {
                                    x: 0,
                                    y: 0,
                                    w: bodyWidth,
                                    h: bodyHeight
                                },
                                inputEl: {
                                    x: borderWidth,
                                    y: borderWidth,
                                    w: bodyWidth - (borderWidth * 2),
                                    h: bodyHeight - (borderWidth * 2)
                                },
                                errorWrapEl: {
                                    x: 0,
                                    y: bodyHeight,
                                    w: width,
                                    h: errorHeight
                                },
                                errorEl: {
                                    x: 0,
                                    y: bodyHeight,
                                    w: width,
                                    h: errorHeight
                                }
                            });
                        });
                    });
                }

                makeSideLabelSuite('left'); // labelAlign: 'left'
                makeSideLabelSuite('right'); // labelAlign: 'right'

                // TODO: EXTJS-12634
                (Ext.isIE10m && !shrinkHeight ? xdescribe : describe)("top label", function() {
                    it("should layout", function() {
                        create({
                            labelAlign: 'top'
                        });

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            '.x-form-item-label-inner': {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            bodyEl: {
                                x: 0,
                                y: labelHeight,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: labelHeight + borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            }
                        });
                        expect(component.errorWrapEl).toBeNull();
                    });

                    it("should layout with side error", function() {
                        create({
                            labelAlign: 'top',
                            msgTarget: 'side'
                        });

                        setError();

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            '.x-form-item-label-inner': {
                                x: 0,
                                y: 0,
                                w: bodyWidth,
                                h: labelHeight
                            },
                            bodyEl: {
                                x: 0,
                                y: labelHeight,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: labelHeight + borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: bodyWidth,
                                y: labelHeight,
                                w: errorWidth,
                                h: bodyHeight
                            },
                            errorEl: {
                                x: bodyWidth + errorIconMargin,
                                y: labelHeight + ((bodyHeight - errorIconSize) / 2),
                                w: errorIconSize,
                                h: errorIconSize
                            }
                        });
                    });

                    it("should layout with hidden side error", function() {
                        create({
                            labelAlign: 'top',
                            msgTarget: 'side'
                        });

                        width = (shrinkWidth && autoFitErrors) ? width - errorWidth : width;
                        var bdWidth = (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth;

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            '.x-form-item-label-inner': {
                                x: 0,
                                y: 0,
                                w: bdWidth,
                                h: labelHeight
                            },
                            bodyEl: {
                                x: 0,
                                y: labelHeight,
                                w: bdWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: labelHeight + borderWidth,
                                w: bdWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: autoFitErrors ? 0 : bodyWidth,
                                y: autoFitErrors ? 0 : labelHeight,
                                w: autoFitErrors ? 0 : errorWidth,
                                h: autoFitErrors ? 0 : bodyHeight
                            },
                            errorEl: {
                                x: autoFitErrors ? 0 : bodyWidth + errorIconMargin,
                                y: autoFitErrors ? 0 : labelHeight + ((bodyHeight - errorIconSize) / 2),
                                w: autoFitErrors ? 0 : errorIconSize,
                                h: autoFitErrors ? 0 : errorIconSize
                            }
                        });
                    });

                    it("should layout with under error", function() {
                        create({
                            labelAlign: 'top',
                            msgTarget: 'under'
                        });

                        setError();

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            '.x-form-item-label-inner': {
                                x: 0,
                                y: 0,
                                w: width,
                                h: labelHeight
                            },
                            bodyEl: {
                                x: 0,
                                y: labelHeight,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: labelHeight + borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: 0,
                                y: labelHeight + bodyHeight,
                                w: width,
                                h: errorHeight
                            },
                            errorEl: {
                                x: 0,
                                y: labelHeight + bodyHeight,
                                w: width,
                                h: errorHeight
                            }
                        });
                    });

                    it("should layout with hidden label", function() {
                        create({
                            labelAlign: 'top',
                            hideLabel: true
                        });

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                xywh: '0 0 0 0'
                            },
                            bodyEl: {
                                x: 0,
                                y: 0,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            }
                        });
                        expect(component.errorWrapEl).toBeNull();
                    });

                    it("should layout with hidden label and side error", function() {
                        create({
                            labelAlign: 'top',
                            hideLabel: true,
                            msgTarget: 'side'
                        });

                        setError();

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                xywh: '0 0 0 0'
                            },
                            bodyEl: {
                                x: 0,
                                y: 0,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: bodyWidth,
                                y: 0,
                                w: errorWidth,
                                h: height
                            },
                            errorEl: {
                                x: bodyWidth + errorIconMargin,
                                y: (bodyHeight - errorIconSize) / 2,
                                w: errorIconSize,
                                h: errorIconSize
                            }
                        });
                    });

                    it("should layout with hidden label and hidden side error", function() {
                        create({
                            labelAlign: 'top',
                            hideLabel: true,
                            msgTarget: 'side'
                        });

                        var bdWidth = (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth;

                        expect(component).toHaveLayout({
                            el: {
                                w: (shrinkWidth && autoFitErrors) ? width - errorWidth : width,
                                h: height
                            },
                            labelEl: {
                                xywh: '0 0 0 0'
                            },
                            bodyEl: {
                                x: 0,
                                y: 0,
                                w: bdWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: borderWidth,
                                w: bdWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: autoFitErrors ? 0 : bodyWidth,
                                y: autoFitErrors ? 0 : 0,
                                w: autoFitErrors ? 0 : errorWidth,
                                h: autoFitErrors ? 0 : height
                            },
                            errorEl: {
                                x: autoFitErrors ? 0 : bodyWidth + errorIconMargin,
                                y: autoFitErrors ? 0 : (bodyHeight - errorIconSize) / 2,
                                w: autoFitErrors ? 0 : errorIconSize,
                                h: autoFitErrors ? 0 : errorIconSize
                            }
                        });
                    });

                    it("should layout with hidden label and under error", function() {
                        create({
                            labelAlign: 'top',
                            hideLabel: true,
                            msgTarget: 'under'
                        });

                        setError();

                        expect(component).toHaveLayout({
                            el: {
                                w: width,
                                h: height
                            },
                            labelEl: {
                                xywh: '0 0 0 0'
                            },
                            bodyEl: {
                                x: 0,
                                y: 0,
                                w: bodyWidth,
                                h: bodyHeight
                            },
                            inputEl: {
                                x: borderWidth,
                                y: borderWidth,
                                w: bodyWidth - (borderWidth * 2),
                                h: bodyHeight - (borderWidth * 2)
                            },
                            errorWrapEl: {
                                x: 0,
                                y: bodyHeight,
                                w: width,
                                h: errorHeight
                            },
                            errorEl: {
                                x: 0,
                                y: bodyHeight,
                                w: width,
                                h: errorHeight
                            }
                        });
                    });
                });
            });
        }

        makeLayoutSuite(0, false); // fixed width and height
        makeLayoutSuite(1, true); // shrinkWrap width, autoFitErrors
        makeLayoutSuite(2, false); // shrinkWrap height
        makeLayoutSuite(2, true); // shrinkWrap height, autoFitErrors
        makeLayoutSuite(3, false); // shrinkWrap both
        makeLayoutSuite(3, true); // shrinkWrap both, autoFitErrors

        it("should work around the webkit min-width table-cell bug", function() {
            // See EXTJS-12665 and https://bugs.webkit.org/show_bug.cgi?id=130239
            var field = Ext.widget({
                xtype: 'textfield',
                renderTo: document.body
            });

            // reflow must happen before setting width in order for the bug to occur.
            // odds are, that a reflow was already triggered during the rendering and
            // layout of the field, but reading offsetWidth ensures that a reflow happens
            // right now, just in case.
            var width = field.el.offsetWidth;

            // set a width smaller than the fields natural dom-width.  natural width in this
            // case the field body's min-width (150) since there is no label.
            field.setWidth(50);

            expect(field.getWidth()).toBe(50);

            field.destroy();
        });

        it("should not stretch the triggerWrap height if the field height expands due to wrapping text in the label", function() {
            var field = Ext.widget({
                xtype: 'textfield',
                renderTo: Ext.getBody(),
                fieldLabel: '<div style="width: 30px; height: 100px;"></div>'
            });

            expect(field.triggerWrap.getHeight()).toBe(22);
            expect(field.triggerWrap.getY() - field.bodyEl.getY()).toBe(48);

            field.destroy();
        });
    });

    // the handling for mousedown in fireMouseEvent doesn't jive with Safari, so disable for now
    var notSafari = Ext.isSafari ? xdescribe : describe;

    notSafari("selectOnFocus", function() {
        function create(select) {
            makeComponent({
                value: 'foo',
                emptyText: 'bar',
                selectOnFocus: select,
                renderTo: document.body
            });
        }

        function getTextSelectionIndices(field) {
            var indices = [];

            if (document.selection) {
                var range = document.selection.createRange(),
                    stored = range.duplicate(),
                    start, len;

                stored.expand('textedit');
                stored.setEndPoint('EndToEnd', range);

                len = range.text.length;
                start = stored.text.length - len;

                indices.push(start);
                indices.push(start + len);
            }
            else {
                indices.push(field.selectionStart);
                indices.push(field.selectionEnd);
            }

            return indices;
        }

        describe("from mouseup", function() {
            it("should not select text when selectOnFocus: false", function() {
                var indices;

                create(false);

                jasmine.fireMouseEvent(component.inputEl, 'mousedown');
                jasmine.fireMouseEvent(component.inputEl, 'mouseup');

                indices = getTextSelectionIndices(component.inputEl.dom);
                // start and end of selection should be the same since selectOnFocus: false
                expect(indices[0]).toBe(indices[1]);
            });

            it("should not select text onFocus when selectOnFocus: false", function() {
                var indices;

                create(false);

                jasmine.focusAndWait(component);

                runs(function() {
                    indices = getTextSelectionIndices(component.inputEl.dom);
                    // start and end of selection should be the same since selectOnFocus: false
                    expect(indices[0]).toBe(indices[1]);
                });
            });

            it("should select text when selectOnFocus: true", function() {
                var indices;

                create(true);

                jasmine.focusAndWait(component);

                if (Ext.isIE) {
                    waits(10);
                }

                runs(function() {
                    indices = getTextSelectionIndices(component.inputEl.dom);
                    // end of selection should be 3 since selectOnFocus: true
                    expect(indices[0]).toBe(0);
                    expect(indices[1]).toBe(3);
                });
            });
        });
    });
});
