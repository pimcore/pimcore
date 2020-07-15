topSuite("Ext.form.field.TextArea", ['Ext.Container', 'Ext.layout.container.Fit'], function() {
    var component;

    function makeComponent(config) {
        config = Ext.apply({
            name: 'test'
        }, config);

        if (component) {
            component.destroy();
        }

        component = new Ext.form.field.TextArea(config);
    }

    afterEach(function() {
        component = Ext.destroy(component);
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

    describe("defaults", function() {
        beforeEach(function() {
            makeComponent();
        });

        it("should have growMin = 60", function() {
            expect(component.growMin).toEqual(60);
        });
        it("should have growMax = 1000", function() {
            expect(component.growMax).toEqual(1000);
        });
        it("should have growAppend = '\n-'", function() {
            expect(component.growAppend).toEqual('\n-');
        });
        it("should have enterIsSpecial = false", function() {
            expect(component.enterIsSpecial).toBe(false);
        });
        it("should have preventScrollbars = false", function() {
            expect(component.preventScrollbars).toBe(false);
        });
    });

    describe("rendering", function() {
        // NOTE this doesn't yet test the main label, error icon, etc. just the parts specific to TextArea.

        beforeEach(function() {
            makeComponent({
                name: 'fieldName',
                value: 'fieldValue',
                tabIndex: 5,
                renderTo: Ext.getBody()
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
            it("should be a textarea element", function() {
                expect(component.inputEl.dom.tagName.toLowerCase()).toEqual('textarea');
            });

            it("should have the component's inputId as its id", function() {
                expect(component.inputEl.dom.id).toEqual(component.inputId);
            });

            it("should have the 'fieldCls' config as a class", function() {
                expect(component.inputEl.hasCls(component.fieldCls)).toBe(true);
            });

            it("should have a class of 'x-form-text'", function() {
                expect(component.inputEl.hasCls('x-form-text')).toBe(true);
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
        });

        describe("ariaEl", function() {
            it("should be inputEl", function() {
                expect(component.ariaEl).toBe(component.inputEl);
            });
        });

        describe("ARIA attributes", function() {
            it("should have textbox role", function() {
                expect(component).toHaveAttr('role', 'textbox');
            });

            it("should have aria-multiline attribute", function() {
                expect(component).toHaveAttr('aria-multiline', 'true');
            });
        });

        xdescribe("sizing", function() {
            it("should have the cols property affect size when shrink wrapping", function() {
                var width = component.getWidth();

                component.destroy();
                makeComponent({
                    rows: 10,
                    cols: 40,
                    renderTo: Ext.getBody()
                });
                expect(component.getWidth()).toBeGreaterThan(width);
                component.destroy();
                makeComponent({
                    rows: 10,
                    cols: 10,
                    renderTo: Ext.getBody()
                });
                expect(component.getWidth()).toBeLessThan(width);
            });

            it("should give preference to a calculated/configured width", function() {
                component.destroy();
                makeComponent({
                    rows: 10,
                    cols: 40,
                    width: 500,
                    renderTo: Ext.getBody()
                });
                expect(component.getWidth()).toBe(500);
            });

            it("should account for a top label when sizing", function() {
                component.destroy();
                makeComponent({
                    renderTo: Ext.getBody(),
                    width: 100,
                    height: 100,
                    labelAlign: 'top',
                    fieldLabel: 'A label'
                });

                var label = component.labelEl,
                    expected = 100 - (label.getHeight() + label.getMargin('tb'));

                expect(component.inputEl.getHeight()).toBe(expected);
            });
        });
    });

    // TODO: https://sencha.jira.com/browse/EXTJS-18488
    (Ext.isIE8 ? xdescribe : describe)("autoSize method and grow configs", function() {
        function makeLines(n) {
            var out = [],
                i;

            for (i = 0; i < n; ++i) {
                out.push('a');
            }

            return out.join('\n');
        }

        describe("with an auto height", function() {
            beforeEach(function() {
                makeComponent({
                    grow: true,
                    growMin: 40,
                    growMax: 200,
                    renderTo: Ext.getBody()
                });
            });

            it("should auto height with an initial value", function() {
                component.destroy();
                makeComponent({
                    grow: true,
                    growMin: 40,
                    growMax: 500,
                    renderTo: Ext.getBody(),
                    value: makeLines(10)
                });
                expect(component.getHeight()).toBeLessThan(500);
                expect(component.getHeight()).toBeGreaterThan(40);
            });

            it("should set the initial textarea height to growMin", function() {
                expect(component.getHeight()).toBe(40);
            });

            it("should autogrow and hide scrollbars when preventScrollbars is true", function() {
                component.destroy();

                makeComponent({
                    grow: true,
                    growMin: 40,
                    growMax: 500,
                    renderTo: Ext.getBody(),
                    preventScrollbars: true,
                    value: makeLines(10)
                });

                expect(component.inputEl.getHeight()).toBeGreaterThan(150);
                expect(component.inputEl.getStyle('overflow-y')).toBe('hidden');
            });

            it("should increase the height of the input as the value becomes taller", function() {
                component.setValue(makeLines(4));
                var height1 = component.getHeight();

                component.setValue(makeLines(5));
                var height2 = component.getHeight();

                expect(height2).toBeGreaterThan(height1);
            });

            it("should decrease the height of the input as the value becomes shorter", function() {
                component.setValue('A\nB\nC\nD\nE');
                var height1 = component.inputEl.getHeight();

                component.setValue('A\nB\nC\nD');
                var height2 = component.inputEl.getHeight();

                expect(height2).toBeLessThan(height1);
            });

            it("should not increase the height above the growMax config", function() {
                component.setValue(makeLines(50));
                var height = component.getHeight();

                expect(height).toBe(200);
            });

            it("should not decrease the height below the growMin config", function() {
                component.setValue('');
                var height = component.getHeight();

                expect(height).toBe(40);
            });

            it("should work with markup", function() {
                component.setValue('<fake tag appears here with longer text that should cause the field to grow');
                expect(component.getHeight()).toBeGreaterThan(40);
            });
        });

        describe("with a fixed height", function() {
            it("should have no effect on a configured height", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    grow: true,
                    growMin: 100,
                    height: 150,
                    growMax: 700
                });
                component.setValue(makeLines(100));
                expect(component.getHeight()).toBe(150);
            });

            it("should have no effect on a calculated height", function() {
                makeComponent({
                    grow: true,
                    growMin: 100,
                    growMax: 700
                });

                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    layout: 'fit',
                    width: 150,
                    height: 150,
                    items: component
                });

                component.setValue(makeLines(100));
                expect(component.getHeight()).toBe(150);
                ct.destroy();
            });
        });
    });

    describe("readOnly", function() {
        describe("readOnly config", function() {
            it("should set the readonly attribute of the field when rendered", function() {
                makeComponent({
                    readOnly: true,
                    renderTo: Ext.getBody()
                });
                expect(component.inputEl.dom.readOnly).toBe(true);
            });
        });

        describe("setReadOnly method", function() {
            it("should set the readOnly state of the field immediately if rendered", function() {
                makeComponent({
                    renderTo: Ext.getBody()
                });
                component.setReadOnly(true);
                expect(component.inputEl.dom.readOnly).toBe(true);
            });

            it("should remember the value if the field has not yet been rendered", function() {
                makeComponent();
                component.setReadOnly(true);
                component.render(Ext.getBody());
                expect(component.inputEl.dom.readOnly).toBe(true);
            });
        });
    });

    describe("preventScrollbars config", function() {
        it("should set overflow:hidden on the textarea if true", function() {
            makeComponent({
                grow: true,
                preventScrollbars: true,
                renderTo: Ext.getBody()
            });
            expect(component.inputEl.getStyle('overflow')).toEqual('hidden');
        });
        it("should should do nothing if preventScrollbars is false", function() {
            makeComponent({
                grow: true,
                preventScrollbars: false,
                renderTo: Ext.getBody()
            });
            expect(component.inputEl.dom.style.overflow).not.toEqual('hidden');
        });
        it("should should do nothing if grow is false", function() {
            makeComponent({
                grow: false,
                preventScrollbars: true,
                renderTo: Ext.getBody()
            });
            expect(component.inputEl.getStyle('overflow')).not.toEqual('hidden');
        });
    });

    describe("initial value", function() {
        var makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty = function(initialValue) {
            makeComponent({
                value: initialValue
            });
            expect(component.getValue()).toBe(initialValue);
            expect(component.isDirty()).toBeFalsy();
        };

        it("should not insert unspecified new lines", function() {
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty(' initial  value ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('  initial   value  ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty(' ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('  ');
        });

        it("should preserve new lines", function() {
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\ninitial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\n\ninitial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('   initial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('   \ninitial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\n   initial value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial\nvalue');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial \n value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial \n\n value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial \n \n value');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial value\n');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('initial value\n\n');
        });

        it("should preserve empty strings", function() {
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\n');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty(' \n ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('  \n  ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty(' \n \n ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('  \n  \n  ');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\n \n');
            makeComponentWithInitialValueAndExpectValueToBeExactAndNonDirty('\n  \n');
        });
    });

    describe("carriage returns", function() {
        var s = 'line1\r\nline2';

        var expectNoCarriageReturns = function() {
            expect(component.getValue().indexOf('\r')).toBe(-1);
        };

        var expectNoCarriageReturnsAndNotDirty = function() {
            expectNoCarriageReturns();
            expect(component.isDirty()).toBe(false);
        };

        it("should strip carriage returns from the initial value before render", function() {
            makeComponent({
                value: s
            });
            expectNoCarriageReturnsAndNotDirty();
        });

        it("should strip carriage returns from the initial value after render", function() {
            makeComponent({
                value: s,
                renderTo: Ext.getBody()
            });
            expectNoCarriageReturnsAndNotDirty();
        });

        it("should strip carriage returns when we call setValue before rendering", function() {
            makeComponent();
            component.setValue(s);
            expectNoCarriageReturns();
        });

        it("should strip carriage returns when we call setValue after rendering", function() {
            makeComponent({
                renderTo: Ext.getBody()
            });
            component.setValue(s);
            expectNoCarriageReturns();
        });
    });

    describe("validation", function() {
        describe("allowBlank", function() {
            it("should not allow only newlines and spaces when used with allowOnlyWhitespace: false", function() {
                makeComponent({
                    allowOnlyWhitespace: false,
                    value: '  \n\n    \n\n'
                });
                expect(component.getErrors()).toContain('This field is required');
            });
        });
    });

    (Ext.isIE8 ? xdescribe : describe)("foo", function() {
        it("should start out at growMin", function() {
            makeComponent({
                renderTo: document.body,
                grow: true,
                growMin: 50
            });

            expect(component.getHeight()).toBe(50);
        });

        it("should initially render at the height of the text", function() {
            makeComponent({
                renderTo: document.body,
                value: 'm\nm\nm\nm\nm\nm\nm',
                grow: true,
                growMin: 50
            });

            expect(component.getHeight()).toBe(117);
        });

        it("should initially render with a height of growMax if initial text height exceeds growMax", function() {
            makeComponent({
                renderTo: document.body,
                value: 'm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm\nm',
                grow: true,
                growMax: 200
            });

            expect(component.getHeight()).toBe(200);
        });

        it("should grow and shrink", function() {
            makeComponent({
                renderTo: document.body,
                grow: true,
                growMin: 50,
                growMax: 100
            });

            expect(component.getHeight()).toBe(50);

            component.setValue('m\nm\nm\nm');

            expect(component.getHeight()).toBe(75);

            component.setValue('m\nm\nm\nm\nm\nm\nm\nm\nm\nm');

            expect(component.getHeight()).toBe(100);

            component.setValue('m\nm\nm\nm');

            expect(component.getHeight()).toBe(75);

            component.setValue('m');

            expect(component.getHeight()).toBe(50);
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
                    bodyHeight = shrinkHeight ? 58 : 100, // the height of the bodyEl
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

                    component = Ext.create('Ext.form.field.TextArea', Ext.apply({
                        renderTo: document.body,
                        height: shrinkHeight ? null : height,
                        width: shrinkWidth ? null : width,
                        autoFitErrors: autoFitErrors,
                        // use a fixed size element vs. text for the field label for
                        // consistency of measurement cross-browser
                        fieldLabel: '<span style="display:inline-block;width:' + labelInnerWidth +
                            'px;background-color:red;">&nbsp;</span>',
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

                        // TODO: EXTJS-12634
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

                        // TODO: EXTJS-12634
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

                        // TODO: EXTJS-12634
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

                        // TODO: EXTJS-12634
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

                        // TODO: EXTJS-12634
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

                        // TODO: EXTJS-12634
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

        (Ext.isIE8 ? xdescribe : describe)("constraints", function() {
            function expectInputHeight(h, offset) {
                var inputPadding = component.inputEl.getPadding('tb');

                offset = offset || 0;
                h -= component.inputWrap.getBorderWidth('tb') + offset;

                expect(component.inputEl.getHeight()).toBeApprox(h, inputPadding);
            }

            it("should stretch the input element with minHeight", function() {
                makeComponent({
                    renderTo: Ext.getBody(),
                    minHeight: 200
                });
                expectInputHeight(200);
            });

            describe("in a layout", function() {
                var ct;

                function makeCt(cfg) {
                    ct = new Ext.container.Container({
                        width: 400,
                        height: 400,
                        renderTo: Ext.getBody(),
                        layout: {
                            type: 'vbox',
                            align: 'stretch'
                        },
                        items: [Ext.apply({
                            xtype: 'textarea',
                            flex: 1
                        }, cfg)]
                    });
                    component = ct.items.first();
                }

                afterEach(function() {
                    ct = Ext.destroy(ct);
                });

                describe("minHeight", function() {
                    it("should favor a minHeight", function() {
                        makeCt({
                            minHeight: 600
                        });
                        expectInputHeight(600);
                    });

                    it("should stretch past a minHeight", function() {
                        makeCt({
                            minHeight: 200
                        });
                        expectInputHeight(400, 5);
                    });
                });

                describe("maxHeight", function() {
                    it("should favor a maxHeight", function() {
                        makeCt({
                            maxHeight: 200
                        });
                        expectInputHeight(200);
                    });

                    it("should narrow under a maxHeight", function() {
                        makeCt({
                            maxHeight: 600
                        });
                        expectInputHeight(400, 5);
                    });
                });
            });
        });
    });
});
