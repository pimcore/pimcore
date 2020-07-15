topSuite("Ext.overrides.dom.Element", [false, 'Ext.dom.Element', 'Ext.window.Window'], function() {
    var E = Ext.dom.Element,
        topEl, el, dom;

    var fakeScope = {
        id: "fakeScope",
        fakeScope: true
    };

    function createElement(markup, selector) {
        if (topEl) {
            topEl.destroy();
        }

        if (Ext.isArray(markup)) {
            markup = markup.join('');
        }

        topEl = Ext.dom.Helper.insertFirst(Ext.getBody(), markup, true);

        el = selector ? topEl.down(selector) : topEl;
        dom = el.dom;
    }

    afterEach(function() {
        if (topEl) {
            topEl.destroy();
        }

        if (el) {
            el.destroy();
        }

        topEl = el = dom = null;
    });

    describe("shim", function() {
        var iframe, win;

        afterEach(function() {
            Ext.destroy(iframe, win);
        });

        it("should mask all iframes when resizing an element with shim and unmask when done.", function() {
            iframe = Ext.getBody().createChild({
                tag: 'iframe',
                src: 'about:blank',
                style: 'position:absolute;left:0px;top:0px;width:200px;height:100px;'
            });

            win = new Ext.window.Window({
                width: 100,
                height: 100,
                title: 'Test',
                shim: true
            }).show();

            jasmine.fireMouseEvent(win.resizer.south, 'mousedown');

            expect(Ext.fly(iframe.dom.parentNode).isMasked()).toBe(true);

            jasmine.fireMouseEvent(win.resizer.south, 'mouseup');

            expect(Ext.fly(iframe.dom.parentNode).isMasked()).toBe(false);
        });
    });

    describe("setVisible", function() {
        var offsetsCls = Ext.baseCSSPrefix + 'hidden-offsets',
            clipCls = Ext.baseCSSPrefix + 'hidden-clip',
            visible = Ext.isIE8 ? 'inherit' : 'visible';

        beforeEach(function() {
            createElement({});
        });

        describe("mode: DISPLAY", function() {
            describe("hiding", function() {
                beforeEach(function() {
                    el.setVisibilityMode(Ext.dom.Element.DISPLAY);
                    el.setVisible(false);
                });

                it("should assign display:none", function() {
                    expect(el.getStyle('display')).toBe('none');
                });

                it("should not assign visibility:hidden", function() {
                    expect(el.getStyle('visibility')).toBe(visible);
                });

                it("should not assign offsetsCls", function() {
                    expect(el.hasCls(offsetsCls)).toBe(false);
                });

                it("should not assign clipCls", function() {
                    expect(el.hasCls(clipCls)).toBe(false);
                });

                describe("showing", function() {
                    beforeEach(function() {
                        el.setVisible(true);
                    });

                    it("should assign display:block", function() {
                        expect(el.getStyle('display')).toBe('block');
                    });

                    it("should not assign visibility:hidden", function() {
                        expect(el.getStyle('visibility')).toBe(visible);
                    });

                    it("should not assign offsetsCls", function() {
                        expect(el.hasCls(offsetsCls)).toBe(false);
                    });

                    it("should not assign clipCls", function() {
                        expect(el.hasCls(clipCls)).toBe(false);
                    });
                });
            });
        });

        describe("mode: VISIBILITY", function() {
            describe("hiding", function() {
                beforeEach(function() {
                    el.setVisibilityMode(Ext.dom.Element.VISIBILITY);
                    el.setVisible(false);
                });

                it("should assign visibility:hidden", function() {
                    expect(el.getStyle('visibility')).toBe('hidden');
                });

                it("should not assign display:none", function() {
                    expect(el.getStyle('display')).toBe('block');
                });

                it("should not assign offsetsCls", function() {
                    expect(el.hasCls(offsetsCls)).toBe(false);
                });

                it("should not assign clipCls", function() {
                    expect(el.hasCls(clipCls)).toBe(false);
                });

                describe("showing", function() {
                    beforeEach(function() {
                        el.setVisible(true);
                    });

                    it("should assign visibility:visible", function() {
                        expect(el.getStyle('visibility')).toBe(visible);
                    });

                    it("should not assign display:none", function() {
                        expect(el.getStyle('display')).toBe('block');
                    });

                    it("should not assign offsetsCls", function() {
                        expect(el.hasCls(offsetsCls)).toBe(false);
                    });

                    it("should not assign clipCls", function() {
                        expect(el.hasCls(clipCls)).toBe(false);
                    });
                });
            });
        });

        describe("mode: OFFSETS", function() {
            describe("hiding", function() {
                beforeEach(function() {
                    el.setVisibilityMode(Ext.dom.Element.OFFSETS);
                    el.setVisible(false);
                });

                it("should assign offsetsCls", function() {
                    expect(el.hasCls(offsetsCls)).toBe(true);
                });

                it("should not assign display:none", function() {
                    expect(el.getStyle('display')).toBe('block');
                });

                it("should not assign clipCls", function() {
                    expect(el.hasCls(clipCls)).toBe(false);
                });

                describe("showing", function() {
                    beforeEach(function() {
                        el.setVisible(true);
                    });

                    it("should reset offsetsCls", function() {
                        expect(el.hasCls(offsetsCls)).toBe(false);
                    });

                    it("should not assign display:none", function() {
                        expect(el.getStyle('display')).toBe('block');
                    });

                    it("should not assign visibility:hidden", function() {
                        expect(el.getStyle('visibility')).toBe(visible);
                    });

                    it("should not assign clipCls", function() {
                        expect(el.hasCls(clipCls)).toBe(false);
                    });
                });
            });
        });

        describe("mode: CLIP", function() {
            describe("hiding", function() {
                beforeEach(function() {
                    el.setVisibilityMode(Ext.dom.Element.CLIP);
                    el.setVisible(false);
                });

                it("should assign clipCls", function() {
                    expect(el.hasCls(clipCls)).toBe(true);
                });

                it("should not assign display:none", function() {
                    expect(el.getStyle('display')).toBe('block');
                });

                it("should not assign visibility:hidden", function() {
                    expect(el.getStyle('visibility')).toBe(visible);
                });

                it("should not assign offsetsCls", function() {
                    expect(el.hasCls(offsetsCls)).toBe(false);
                });

                describe("showing", function() {
                    beforeEach(function() {
                        el.setVisible(true);
                    });

                    it("should reset clipCls", function() {
                        expect(el.hasCls(clipCls)).toBe(false);
                    });

                    it("should not assign display:none", function() {
                        expect(el.getStyle('display')).toBe('block');
                    });

                    it("should not assign visibility:hidden", function() {
                        expect(el.getStyle('visibility')).toBe(visible);
                    });

                    it("should not assign offsetsCls", function() {
                        expect(el.hasCls(offsetsCls)).toBe(false);
                    });
                });
            });
        });
    });

    describe("masking", function() {
        describe("isMasked", function() {
            beforeEach(function() {
                createElement({
                    tag: 'div',
                    id: 'foo',
                    cn: [{
                        tag: 'div',
                        id: 'bar',
                        cn: [{
                            tag: 'div',
                            id: 'baz'
                        }]
                    }]
                }, '#bar');
            });

            afterEach(function() {
                Ext.getBody().unmask();
            });

            it("should be false when no elements are masked", function() {
                expect(el.isMasked()).toBe(false);
            });

            it("should be false when child element is masked", function() {
                var baz = el.down('#baz');

                baz.mask();

                expect(el.isMasked()).toBe(false);

                baz.destroy();
            });

            it("should be true when el is masked", function() {
                el.mask();

                expect(el.isMasked()).toBe(true);
            });

            it("should be false when !hierarchy and the parent is masked", function() {
                topEl.mask();

                expect(el.isMasked()).toBe(false);
            });

            it("should be true when hierarchy === true and parent is masked", function() {
                topEl.mask();

                expect(el.isMasked(true)).toBe(true);
            });

            it("should be true when hierarchy === true and body is masked", function() {
                Ext.getBody().mask();

                expect(el.isMasked(true)).toBe(true);
            });
        });

        describe("an element", function() {
            beforeEach(function() {
                createElement([
                    '<div id="foo" tabindex="0">',
                        '<input id="bar" />',
                        '<div id="baz">',
                            '<textarea id="qux"></textarea>',
                        '</div>',
                    '</div>'
                ]);
            });

            describe("when masked", function() {
                beforeEach(function() {
                    el.mask();
                });

                it("should save its tabbable state", function() {
                    expect(el.isTabbable()).toBeFalsy();
                });

                it("should save its children tabbable states", function() {
                    var tabbables = el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(0);
                });
            });

            describe("when unmasked", function() {
                beforeEach(function() {
                    el.mask();
                    el.unmask();
                });

                it("should restore its tabbable state", function() {
                    expect(el.isTabbable()).toBeTruthy();
                });

                it("should restore its children tabbable state", function() {
                    var tabbables = el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(2);
                });
            });
        });

        describe("document body", function() {
            var el, saved;

            beforeEach(function() {
                createElement([
                    '<div id="foo" tabindex="0">',
                        '<input id="bar" />',
                        '<div id="baz">',
                            '<textarea id="qux"></textarea>',
                        '</div>',
                    '</div>'
                ]);

                el = Ext.getBody();
                saved = el.isTabbable();
            });

            afterEach(function() {
                saved = undefined;
                el.unmask();
            });

            describe("when masked", function() {
                beforeEach(function() {
                    el.mask();
                });

                it("should not change its tabbable state", function() {
                    expect(el.isTabbable()).toBe(saved);
                });

                it("should save its children tabbable states", function() {
                    var tabbables = el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(0);
                });
            });

            describe("when unmasked", function() {
                beforeEach(function() {
                    el.mask();
                    el.unmask();
                });

                it("should not change its tabbable state", function() {
                    expect(el.isTabbable()).toBe(saved);
                });

                it("should restore its children tabbable states", function() {
                    var tabbables = el.findTabbableElements({
                        skipSelf: true
                    });

                    expect(tabbables.length).toBe(3);
                });
            });
        });
    });

    function describeMethods(fly) {
        describe('methods (using ' + (fly ? 'Ext.fly()' : 'new Ext.dom.Element()') + ')', function() {
            var domEl, element;

            function addElement(tag) {
                domEl = document.createElement(tag || 'div');
                document.body.appendChild(domEl);

                return fly ? Ext.fly(domEl) : Ext.get(domEl);
            }

            afterEach(function() {
                if (element) {
                    // Prevent console warnings
                    spyOn(Ext.Logger, 'warn');
                    element.destroy();
                    element = null;
                }
            });

            describe("hover", function() {
                var overFn, outFn, options;

                beforeEach(function() {
                    element = addElement('div');

                    overFn = function() {
                        return 1;
                    };

                    outFn = function() {
                        return 2;
                    };

                    options = {
                        foo: true
                    };

                    spyOn(element, "on");
                });

                describe("mouseenter event", function() {
                    it("should add a listener on mouseenter", function() {
                        element.hover(overFn, outFn, fakeScope, options);

                        expect(element.on).toHaveBeenCalledWith("mouseenter", overFn, fakeScope, options);
                    });

                    it("should set scope to element.dom if it is not passed in arguments", function() {
                        element.hover(overFn, outFn, null, options);

                        expect(element.on).toHaveBeenCalledWith("mouseenter", overFn, element.dom, options);
                    });
                });

                describe("mouseleave event", function() {
                    it("should add a listener on mouseleave", function() {
                        element.hover(overFn, outFn, fakeScope, options);

                        expect(element.on).toHaveBeenCalledWith("mouseleave", outFn, fakeScope, options);
                    });

                    it("should set scope to element.dom if it is not passed in arguments", function() {
                        element.hover(overFn, outFn, null, options);

                        expect(element.on).toHaveBeenCalledWith("mouseleave", outFn, element.dom, options);
                    });
                });
            });

            if (!fly) {
                describe("setVertical", function() {
                    beforeEach(function() {
                        var styleSheet = document.styleSheets[0],
                            selector = '.vert',
                            props = [
                                '-webkit-transform: rotate(90deg);',
                                '-moz-transform: rotate(90deg);',
                                '-o-transform: rotate(90deg);',
                                '-ms-transform: rotate(90deg);', // IE9
                                'transform: rotate(90deg);'
                            ];

                        // SASS mixin only applies filter in IE8
                        if (Ext.isIE8) {
                            props.push('filter: progid:DXImageTransform.Microsoft.BasicImage(rotation=1);');
                        }

                        props = props.join('');

                        if (styleSheet.insertRule) {
                            styleSheet.insertRule(selector + '{' + props + '}', styleSheet.cssRules.length);
                        }
                        else {
                            // IE8
                            styleSheet.addRule(selector, props);
                        }

                        element = addElement('div');
                        element.setWidth(100);
                        element.setHeight(30);

                        element.setVertical(90, 'vert');
                    });

                    afterEach(function() {
                        var styleSheet = document.styleSheets[0];

                        if (styleSheet.deleteRule) {

                            styleSheet.deleteRule(1);
                        }
                        else {
                            // IE8
                            styleSheet.removeRule(styleSheet.rules.length - 1);
                        }
                    });

                    it("should add the css class", function() {
                        expect(element.hasCls('vert')).toBe(true);
                    });

                    it("should get the width using getWidth()", function() {
                        expect(element.getWidth()).toBe(30);
                    });

                    it("should get the width using getStyle('width')", function() {
                        expect(element.getStyle('width')).toBe('30px');
                    });

                    it("should get the height using getHeight", function() {
                        expect(element.getHeight()).toBe(100);
                    });

                    it("should get the height using getStyle('height')", function() {
                        expect(element.getStyle('height')).toBe('100px');
                    });

                    it("should set the width using setWidth()", function() {
                        element.setWidth(200);
                        expect(element.getWidth()).toBe(200);
                    });

                    it("should set the width using setStyle('width')", function() {
                        element.setStyle('width', '200px');
                        expect(element.getWidth()).toBe(200);
                    });

                    it("should set the height using setHeight()", function() {
                        element.setHeight(200);
                        expect(element.getHeight()).toBe(200);
                    });

                    it("should set the height using setStyle('height')", function() {
                        element.setStyle('height', '200px');
                        expect(element.getHeight()).toBe(200);
                    });

                    describe("setHorizontal", function() {
                        beforeEach(function() {
                            element.setHorizontal();
                        });

                        it("should remove the css class", function() {
                            expect(element.hasCls('vert')).toBe(false);
                        });

                        it("should get the width using getWidth()", function() {
                            expect(element.getWidth()).toBe(100);
                        });

                        it("should get the width using getStyle('width')", function() {
                            expect(element.getStyle('width')).toBe('100px');
                        });

                        it("should get the height using getHeight", function() {
                            expect(element.getHeight()).toBe(30);
                        });

                        it("should get the height using getStyle('height')", function() {
                            expect(element.getStyle('height')).toBe('30px');
                        });

                        it("should set the width using setWidth()", function() {
                            element.setWidth(200);
                            expect(element.getWidth()).toBe(200);
                        });

                        it("should set the width using setStyle('width')", function() {
                            element.setStyle('width', '200px');
                            expect(element.getWidth()).toBe(200);
                        });

                        it("should set the height using setHeight()", function() {
                            element.setHeight(200);
                            expect(element.getHeight()).toBe(200);
                        });

                        it("should set the height using setStyle('height')", function() {
                            element.setStyle('height', '200px');
                            expect(element.getHeight()).toBe(200);
                        });
                    });
                });
            }
        });
    }

    describeMethods();
    describeMethods(true);
});
