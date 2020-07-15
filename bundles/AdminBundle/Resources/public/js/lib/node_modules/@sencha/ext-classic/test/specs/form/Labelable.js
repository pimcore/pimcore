topSuite('Ext.form.Labelable', ['Ext.Component'], function() {
    var separator = ':',
        component;

    function define(props) {
        Ext.define('spec.Labelable', Ext.apply({
            extend: 'Ext.Component',
            mixins: [ 'Ext.form.Labelable' ],
            initComponent: function() {
                this.callParent();
                this.initLabelable();
            },
            initRenderData: function() {
                return Ext.applyIf(this.callParent(), this.getLabelableRenderData());
            },
            privates: {
                initRenderTpl: function() {
                    this.renderTpl = this.lookupTpl('labelableRenderTpl');

                    return this.callParent();
                }
            }
        }, props));
    }

    function create(cfg) {
        component = Ext.create('spec.Labelable', Ext.apply({
            renderTo: Ext.getBody()
        }, cfg));
    }

    afterEach(function() {
        component.destroy();
        Ext.undefine('spec.Labelable');
    });

    describe("rendering", function() {
        beforeEach(function() {
            define({
                ui: 'derp',
                labelClsExtra: 'spec-label-extra',
                fieldBodyCls: 'spec-body-cls',
                extraFieldBodyCls: 'spec-body-extra',
                getSubTplMarkup: function() {
                    return '<div style="height:50px;width:150px;background-color:green;"></div>';
                }
            });
        });

        describe("child els", function() {
            var proto;

            beforeEach(function() {
                proto = spec.Labelable.prototype;
            });

            it("should have a labelEl Element as it's first child", function() {
                create();
                expect(component.el.first()).toBe(component.labelEl);
            });

            it("should set labelCls on the labelEl", function() {
                create();
                expect(component.labelEl).toHaveCls(proto.labelCls);
            });

            it("should set labeCls with UI on the labelEl", function() {
                create();
                expect(component.labelEl).toHaveCls(proto.labelCls + '-derp');
            });

            it("should set labelClsExtra on the labelEl", function() {
                create();
                expect(component.labelEl).toHaveCls('spec-label-extra');
            });

            it("should add the unselectable cls to the labelEl", function() {
                create();
                expect(component.labelEl).toHaveCls('x-unselectable');
            });

            it("should have a bodyEl after the labelEl", function() {
                create();
                expect(component.labelEl.next()).toBe(component.bodyEl);
            });

            it("should set baseBodyCls on the bodyEl", function() {
                create();
                expect(component.bodyEl).toHaveCls(proto.baseBodyCls);
            });

            it("should set baseBodyCls with UI on the bodyEl", function() {
                create();
                expect(component.bodyEl).toHaveCls(proto.baseBodyCls + '-derp');
            });

            it("should set fieldBodyCls on the bodyEl", function() {
                create();
                expect(component.bodyEl).toHaveCls(proto.fieldBodyCls);
            });

            it("should set fieldBodyCls with UI on the bodyEl", function() {
                create();
                expect(component.bodyEl).toHaveCls(proto.fieldBodyCls + '-derp');
            });

            it("should set extraFieldBodyCls on the bodyEl", function() {
                create();
                expect(component.bodyEl).toHaveCls(proto.extraFieldBodyCls);
            });

            it("should not render an errorEl by default", function() {
                create();
                expect(component.errorWrapEl).toBeNull();
                expect(component.errorEl).toBeNull();
            });

            it("should render an errorEl if msgTarget is 'side'", function() {
                create({
                    msgTarget: 'side'
                });
                expect(component.bodyEl.next()).toBe(component.errorWrapEl);
                expect(component.errorWrapEl.first()).toBe(component.errorEl);
            });

            it("should render an errorEl if msgTarget is 'under'", function() {
                create({
                    msgTarget: 'under'
                });
                expect(component.bodyEl.next()).toBe(component.errorWrapEl);
                expect(component.errorWrapEl.first()).toBe(component.errorEl);
            });

            it("should not render ariaErrorEl by default", function() {
                create();

                expect(component.ariaErrorEl).toBe(null);
            });

            it("should render ariaErrorEl with renderAriaElements", function() {
                create({ renderAriaElements: true });

                expect(component.ariaErrorEl.dom).toBeDefined();
            });

            it("should assign x-hidden-clip to ariaErrorEl", function() {
                create({ renderAriaElements: true });

                expect(component.ariaErrorEl.hasCls('x-hidden-clip')).toBe(true);
            });

            it("should set ARIA attributes on the ariaErrorEl", function() {
                create({ renderAriaElements: true });

                expect(component.ariaErrorEl).toHaveAttr('aria-hidden', 'true');
                expect(component.ariaErrorEl).toHaveAttr('aria-live', 'assertive');
            });

            it("should not render ariaStatusEl by default", function() {
                create();

                expect(component.ariaStatusEl).toBe(null);
            });

            it("should render ariaStatusEl with renderAriaElements", function() {
                create({ renderAriaElements: true });

                expect(component.ariaStatusEl.dom).toBeDefined();
            });

            it("should assign hidden-offsets to ariaStatusEl", function() {
                create({ renderAriaElements: true });

                expect(component.ariaStatusEl.hasCls('x-hidden-offsets')).toBe(true);
            });

            it("should set ARIA attributes on ariaStatusEl", function() {
                create({ renderAriaElements: true });

                expect(component.ariaStatusEl).toHaveAttr('aria-hidden', 'true');
            });

            it("should not render ariaHelpEl by default", function() {
                create();

                expect(component.ariaHelpEl).toBe(null);
            });

            it("should not render ariaHelpEl with renderAriaElements when ariaHelp is empty", function() {
                create({ renderAriaElements: true });

                expect(component.ariaHelpEl).toBe(null);
            });

            it("should render ariaHelpEl when ariaHelp is configured", function() {
                create({
                    renderAriaElements: true,
                    ariaHelp: 'foo bar'
                });

                expect(component.ariaHelpEl.dom).toBeDefined();
            });

            it("should assign hidden-offsets to ariaHelpEl when rendered", function() {
                create({
                    renderAriaElements: true,
                    ariaHelp: 'blerg throbbe'
                });

                expect(component.ariaHelpEl.hasCls('x-hidden-offsets')).toBe(true);
            });
        });

        describe("fieldLabel and labelSeparator", function() {
            it("should render a hidden label if no fieldLabel was configured", function() {
                create();
                expect(component.labelEl.isVisible()).toBe(false);
            });

            it("should render a hidden label if hideLabel:true was configured", function() {
                create({
                    fieldLabel: 'Label',
                    hideLabel: true
                });
                expect(component.labelEl.isVisible()).toBe(false);
            });

            it("should render a visible label if fieldLabel was configured", function() {
                create({
                    fieldLabel: 'Label'
                });
                expect(component.labelEl.isVisible()).toBe(true);
            });

            it("should render the fieldLabel into the labelEl", function() {
                create({
                    fieldLabel: 'Label'
                });

                expect(component.labelTextEl.dom).hasHTML('Label:');
            });

            it("should render the labelSeparator after the label", function() {
                create({
                    fieldLabel: 'Label',
                    labelSeparator: '-'
                });

                expect(component.labelTextEl.dom).hasHTML('Label-');
            });

            it("should not render the separator if labelSeparator is empty", function() {
                create({
                    fieldLabel: 'Label',
                    labelSeparator: ''
                });

                expect(component.labelTextEl.dom).hasHTML('Label');
            });

            describe("labelStyle", function() {
                it("should add the labelStyle to the labelEl", function() {
                    create({
                        fieldLabel: 'Foo',
                        labelStyle: 'border-top: 50px solid red;'
                    });
                    expect(component.labelEl.getStyle('border-top-width')).toBe('50px');
                });
            });
        });
    });

    describe("methods", function() {
        describe("setFieldLabel", function() {
            beforeEach(function() {
                define({
                    getSubTplMarkup: function() {
                        return '<div style="background-color:green;width:200px;height:50px;"></div>';
                    }
                });
            });

            it("should set the label element's innerHTML", function() {
                create();
                component.setFieldLabel('foo');
                expect(component.labelTextEl.dom).hasHTML('foo' + separator);
            });

            it("should show the label element", function() {
                create();
                component.setFieldLabel('foo');
                expect(component.labelEl.isVisible()).toBe(true);
            });

            it("should hide the label element when setting an empty label", function() {
                create({
                    fieldLabel: 'foo'
                });
                component.setFieldLabel('');
                expect(component.labelEl.isVisible()).toBe(false);
            });

            describe("with under error", function() {
                it("should add the 'x-form-error-wrap-under-side-label' cls to the errorWrapEl when the label is on the side", function() {
                    create({
                        msgTarget: 'under'
                    });
                    component.setFieldLabel('foo');
                    expect(component.errorWrapEl).toHaveCls('x-form-error-wrap-under-side-label');
                });

                it("should not add the 'x-form-error-wrap-under-side-label' cls to the errorWrapEl when the label is on the top", function() {
                    create({
                        msgTarget: 'under',
                        labelAlign: 'top'
                    });
                    component.setFieldLabel('foo');
                    expect(component.errorWrapEl).not.toHaveCls('x-form-error-wrap-under-side-label');
                });

                it("should remove the 'x-form-error-wrap-under-side-label' cls from the errorWrapEl when empty label is set", function() {
                    create({
                        msgTarget: 'under',
                        fieldLabel: 'foo'
                    });
                    component.setFieldLabel('');
                    expect(component.errorWrapEl).not.toHaveCls('x-form-error-wrap-under-side-label');
                });
            });
        });

        describe("setHideLabel", function() {
            beforeEach(function() {
                define({
                    getSubTplMarkup: function() {
                        return '<div></div>';
                    }
                });
            });

            describe("before render", function() {
                it("should hide the label when rendered", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideLabel: false,
                        renderTo: null
                    });
                    component.setHideLabel(true);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should show the label when rendered", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideLabel: true,
                        renderTo: null
                    });
                    component.setHideLabel(false);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(true);
                });
            });

            describe("after render", function() {
                it("should hide the label", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideLabel: false
                    });
                    component.setHideLabel(true);
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should show the label", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideLabel: true
                    });
                    component.setHideLabel(false);
                    expect(component.labelEl.isVisible()).toBe(true);
                });

                it("should run a layout", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideLabel: true
                    });
                    var count = component.componentLayoutCounter;

                    component.setHideLabel(false);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                    count = component.componentLayoutCounter;
                    component.setHideLabel(true);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                });
            });
        });

        describe("setHideEmptyLabel", function() {
            beforeEach(function() {
                define({
                    getSubTplMarkup: function() {
                        return '<div></div>';
                    }
                });
            });

            describe("before render", function() {
                it("should hide if the label is empty when rendered", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: false,
                        renderTo: null
                    });
                    component.setHideEmptyLabel(true);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should show if the label is empty when rendered", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: true,
                        renderTo: null
                    });
                    component.setHideEmptyLabel(false);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(true);
                });

                it("should not be visible if hideLabel: true is configured", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: true,
                        hideLabel: true,
                        renderTo: null
                    });
                    component.setHideEmptyLabel(false);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should not hide if the label is not empty", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideEmptyLabel: false,
                        renderTo: null
                    });
                    component.setHideEmptyLabel(true);
                    component.render(Ext.getBody());
                    expect(component.labelEl.isVisible()).toBe(true);
                });
            });

            describe("after render", function() {
                it("should hide if the label is empty", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: false
                    });
                    component.setHideEmptyLabel(true);
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should show if the label is empty", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: true
                    });
                    component.setHideEmptyLabel(false);
                    expect(component.labelEl.isVisible()).toBe(true);
                });

                it("should not be visible if hideLabel: true is configured", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: true,
                        hideLabel: true
                    });
                    component.setHideEmptyLabel(false);
                    expect(component.labelEl.isVisible()).toBe(false);
                });

                it("should not hide if the label is not empty", function() {
                    create({
                        fieldLabel: 'Foo',
                        hideEmptyLabel: false
                    });
                    component.setHideEmptyLabel(true);
                    expect(component.labelEl.isVisible()).toBe(true);
                });

                it("should run a layout", function() {
                    create({
                        fieldLabel: '',
                        hideEmptyLabel: true
                    });
                    var count = component.componentLayoutCounter;

                    component.setHideEmptyLabel(false);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                    count = component.componentLayoutCounter;
                    component.setHideEmptyLabel(true);
                    expect(component.componentLayoutCounter).toBe(count + 1);
                });
            });
        });

        describe("setActiveError/unsetActiveError", function() {
            var ariaErrorEl, ariaStatusEl;

            beforeEach(function() {
                define({
                    renderAriaElements: true,
                    getSubTplMarkup: function() {
                        return '<div></div>';
                    }
                });

                create();

                ariaErrorEl = component.ariaErrorEl;
                ariaStatusEl = component.ariaStatusEl;
            });

            afterEach(function() {
                ariaErrorEl = ariaStatusEl = null;
            });

            describe("msgTarget == 'title'", function() {
                beforeEach(function() {
                    component.msgTarget = 'title';
                    component.setActiveErrors(['foo', 'bar']);
                });

                it("should not set ariaErrorEl text", function() {
                    expect(ariaErrorEl.dom.innerHTML).toBe('');
                });

                it("should not set ariaStatusEl text", function() {
                    expect(ariaStatusEl.dom.innerHTML).toBe('');
                });
            });

            describe("setActiveErrors", function() {
                beforeEach(function() {
                    component.setActiveErrors(['foo', 'bar']);
                });

                it("should set ariaErrorEl text", function() {
                    expect(ariaErrorEl.dom.innerHTML).toBe('Input error. foo. bar.');
                });

                it("should not change ariaErrorEl content for the same error text", function() {
                    var textNode = ariaErrorEl.dom.firstChild;

                    component.setActiveErrors(['foo', 'bar']);

                    expect(ariaErrorEl.dom.firstChild).toBe(textNode);
                });

                it("should set ariaStatusEl text", function() {
                    expect(ariaStatusEl.dom.innerHTML).toBe('foo. bar');
                });

                describe("unsetActiveError", function() {
                    beforeEach(function() {
                        component.unsetActiveError();
                    });

                    it("should clear ariaErrorEl text", function() {
                        expect(ariaErrorEl.dom.innerHTML).toBe('');
                    });

                    it("should clear ariaStatusEl text", function() {
                        expect(ariaStatusEl.dom.innerHTML).toBe('');
                    });
                });
            });
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
                    bodyWidth = 150, // the width of the bodyEl
                    bodyHeight = 50, // the height of the bodyEl
                    labelHeight = 23, // the height of the label when top aligned
                    hideLabel, topLabel,  width, height;

                beforeEach(function() {
                    define({
                        getSubTplMarkup: function() {
                            return '<div style="background-color:green;' +
                                'width:' + (shrinkWidth ? (bodyWidth + 'px;') : 'auto;') +
                                'height:' + (shrinkHeight ? (bodyHeight + 'px;') : '100%;') +
                                '"></div>';
                        }
                    });
                });

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

                    component = Ext.create('spec.Labelable', Ext.apply({
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

                        it("should layout", function() {
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
                                }
                            });
                            expect(component.errorWrapEl).toBeNull();
                        });

                        it("should layout with side error", function() {
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

                        it("should layout with hidden side error", function() {
                            create({
                                labelAlign: labelAlign,
                                msgTarget: 'side'
                            });

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
                                    w: (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth,
                                    h: bodyHeight
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

                        // TODO: EXTJSIV-12634
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

                        it("should layout with hidden label", function() {
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

                        it("should layout with hidden label and side error", function() {
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
                                labelAlign: labelAlign,
                                hideLabel: true,
                                msgTarget: 'side'
                            });

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
                                    w: (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth,
                                    h: bodyHeight
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

                        // TODO: EXTJSIV-12634
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

                // TODO: EXTJSIV-12634
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
                                w: (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth,
                                h: labelHeight
                            },
                            bodyEl: {
                                x: 0,
                                y: labelHeight,
                                w: (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth,
                                h: bodyHeight
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
                                w: (autoFitErrors && !shrinkWidth) ? bodyWidth + errorWidth : bodyWidth,
                                h: bodyHeight
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
    });
});
