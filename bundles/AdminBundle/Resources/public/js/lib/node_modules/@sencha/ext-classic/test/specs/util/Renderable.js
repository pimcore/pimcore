topSuite("Ext.util.Renderable",
    ['Ext.Panel', 'Ext.container.Viewport', 'Ext.layout.container.Border'],
function() {
    describe('framing', function() {
        var comp,
            styleEl;

        afterEach(function() {
            Ext.destroy(comp);
            comp = null;

            if (styleEl) {
                styleEl.destroy();
                styleEl = null;
            }
        });

        function createComp(framing) {
            var supportsBorderRadius = Ext.supports.CSS3BorderRadius,
                CSS = Ext.util.CSS;

            CSS.createStyleSheet(
                '.style-proxy { font-family: ' + framing + ' }',
                'renderable-test-stylesheet'
            );

            Ext.supports.CSS3BorderRadius = false;

            styleEl = Ext.getBody().createChild({
                cls: 'style-proxy'
            });

            comp = new Ext.Component({
                frame: true,
                getStyleProxy: function() {
                    return styleEl;
                }
            });
            comp.getFrameInfo();

            CSS.removeStyleSheet('renderable-test-stylesheet');
            Ext.supports.CSS3BorderRadius = supportsBorderRadius;
        }

        describe("getFrameInfo", function() {
            it('should return framing info', function() {
                createComp('dh-1-2-3-4');

                var frameInfo = comp.frameSize;

                expect(frameInfo.table).toBe(false);
                expect(frameInfo.vertical).toBe(false);

                expect(frameInfo.top).toBe(1);
                expect(frameInfo.right).toBe(2);
                expect(frameInfo.bottom).toBe(3);
                expect(frameInfo.left).toBe(4);

                expect(frameInfo.width).toBe(6);
                expect(frameInfo.height).toBe(4);
            });
        });

        describe("getFrameRenderData", function() {
            beforeEach(function() {
                createComp('dh-1-2-3-4');
            });

            it("should include id in frame render data", function() {
                var data = comp.getFrameRenderData();

                expect(data.id).toBe(comp.id);
            });
        });

    }); // framing

    describe('Using existing el', function() {
        var viewport,
            previousNodes,
            existingElement;

        beforeEach(function() {
            // The content of the body is being checked by this test so we have to empty it
            var n = document.body.childNodes,
                len = n.length,
                i;

            // Temporarily pull all content out of the document.
            // We need to put it back in case any of it is being left erroneously to be picked up by Jasmine
            previousNodes = document.createDocumentFragment();

            for (i = 0; i < len; i++) {
                previousNodes.appendChild(n[0]);
            }
        });
        afterEach(function() {
            viewport.destroy();

            existingElement = Ext.destroy(existingElement);

            // Restore previous state of document
            document.body.appendChild(previousNodes);
        });

        it('should incorporate existing DOM into the Component tree', function() {
            Ext.getBody().createChild({
                tag: 'div',
                id: 'existing-element',
                cn: {
                    tag: 'ul',
                    cn: [{
                        tag: 'li',
                        html: '<a href="http://www.sencha.com">Sencha</a>'
                    }, {
                        tag: 'li',
                        html: '<a href="http://www.google.com">Google</a>'
                    }]
                }
            });

            viewport = new Ext.container.Viewport({
                layout: 'border',
                items: [{
                    region: 'north',
                    ariaRole: 'foo',
                    xtype: 'component',
                    el: 'existing-element'
                }, {
                    xtype: 'panel',
                    ariaRole: 'bar',
                    id: 'test-panel',
                    region: 'center',
                    html: "test"
                }]
            });

            // Compare to known, correct DOM structure without possibly variable
            // style and class and role and data-ref attributes
            var htmlRe = /\s*(class|style|role|data-ref|aria-\w+)=(?:"[^"]*"|[^> ]+\b)/g,
                have = viewport.el.dom.innerHTML.replace(htmlRe, '')
                                                .replace(/\s{2,}/g, ' ');

            var want = (Ext.isIE8
                ? [
                    '<DIV id=existing-element> ',
                        '<UL> ',
                            '<LI>',
                                '<A href="http://www.sencha.com">Sencha</A>',
                            '</LI> ',
                            '<LI>',
                                '<A href="http://www.google.com">Google</A>',
                            '</LI>',
                        '</UL>',
                    '</DIV> ',
                    '<DIV id=test-panel> ',
                        '<DIV id=test-panel-bodyWrap> ',
                            '<DIV id=test-panel-body> ',
                                '<DIV id=test-panel-outerCt> ',
                                    '<DIV id=test-panel-innerCt>test</DIV>',
                                '</DIV>',
                            '</DIV>',
                        '</DIV>',
                    '</DIV>'
                ]
                : [
                    '<div id="existing-element">',
                        '<ul>',
                            '<li>',
                                '<a href="http://www.sencha.com">Sencha</a>',
                            '</li>',
                            '<li>',
                                '<a href="http://www.google.com">Google</a>',
                            '</li>',
                        '</ul>',
                    '</div>',
                    '<div id="test-panel">',
                        '<div id="test-panel-bodyWrap">',
                            '<div id="test-panel-body">',
                                '<div id="test-panel-outerCt">',
                                    '<div id="test-panel-innerCt">test</div>',
                                '</div>',
                            '</div>',
                        '</div>',
                    '</div>'
                ]).join('');

            if (Ext.isIE8) {
                want = want.replace(/(<\/?)div/g, '$1DIV')
                           .replace(/(<\/?)ul/g, '$1UL')
                           .replace(/(<\/?)li/g, '$1LI')
                           .replace(/(<\/?)a/g, '$1A');
            }

            expect(have).toBe(want);
        });
    });

    describe("Accessibility", function() {
        var c, ariaEl, ariaDom;

        function makeCmp(config) {
            config = Ext.apply({
                renderTo: Ext.getBody(),
                width: 100,
                height: 100,

                maskOnDisable: false,

                style: {
                    'background-color': 'green'
                },

                // Note no childEls by default
                renderTpl: [
                    '<div id="{id}-wrapEl" data-ref="wrapEl" {ariaAttributes:attributes}>',
                        '<div id="{id}-labelEl" data-ref="labelEl" class="label"></div>',
                        '<div id="{id}-descEl" data-ref="descEl" class="desc"></div>',
                    '</div>'
                ]
            }, config);

            c = new Ext.Component(config);

            ariaEl = c.ariaEl;
            ariaDom = ariaEl.dom;

            return c;
        }

        afterEach(function() {
            if (c) {
                Ext.destroy(
                    Ext.get(c.id + '-labelEl'),
                    Ext.get(c.id + '-descEl'),
                    Ext.get(c.id + '-wrapEl')
                );

                c.destroy();
            }

            c = ariaEl = ariaDom = null;
        });

        describe("ariaEl", function() {
            it("should be defined before rendering", function() {
                makeCmp({ renderTo: undefined });

                expect(c.ariaEl).toBeDefined();
            });

            it("should default to main el", function() {
                makeCmp();

                expect(ariaEl).toBe(c.el);
            });

            it("should resolve ariaEl after rendering", function() {
                makeCmp({
                    childEls: ['wrapEl'],
                    ariaEl: 'wrapEl'
                });

                expect(ariaEl).toBe(c.wrapEl);
            });
        });

        describe("attributes", function() {
            function makeAttrSuite(desc, defaultConfig) {
                describe(desc, function() {
                    var onMainEl = !defaultConfig || !defaultConfig.ariaEl ||
                                    defaultConfig.ariaEl === 'el',
                        shouldMain = onMainEl ? "should" : "should not",
                        shouldWrap = onMainEl ? "should not" : "should",
                        el, dom, wrapEl, wrapDom, expectMain, expectWrap;

                    function makeC(config) {
                        config = Ext.apply({}, config, defaultConfig);

                        makeCmp(config);

                        el = c.el;
                        dom = el.dom;

                        wrapEl = c.wrapEl;
                        wrapDom = wrapEl.dom;
                    }

                    function attrIt(desc, attr, want) {
                        it(shouldMain + " " + desc + " on main el", function() {
                            expectMain(attr, want);
                        });

                        it(shouldWrap + " " + desc + " on wrapEl", function() {
                            expectWrap(attr, want);
                        });
                    }

                    beforeEach(function() {
                        expectMain = function(attr, want) {
                            if (typeof want === 'function') {
                                want = want();
                            }

                            if (onMainEl) {
                                var have = c.el.dom.getAttribute(attr);

                                expect(have).toBe(want);
                            }
                            else {
                                expect(c.el.dom.hasAttribute(attr)).toBe(false);
                            }
                        };

                        expectWrap = function(attr, want) {
                            if (typeof want === 'function') {
                                want = want();
                            }

                            if (!onMainEl) {
                                var have = c.wrapEl.dom.getAttribute(attr);

                                expect(have).toBe(want);
                            }
                            else {
                                expect(c.wrapEl.dom.hasAttribute(attr)).toBe(false);
                            }
                        };
                    });

                    afterEach(function() {
                        el = dom = wrapEl = wrapDom = null;
                    });

                    describe("basic", function() {
                        describe("role undefined", function() {
                            beforeEach(function() {
                                makeC();
                            });

                            it("should not render role on el", function() {
                                expect(ariaDom.hasAttribute('role')).toBe(false);
                            });

                            it("should not render role on wrapEl", function() {
                                expect(wrapDom.hasAttribute('role')).toBe(false);
                            });
                        });

                        describe("role defined", function() {
                            beforeEach(function() {
                                makeC({ ariaRole: 'foo' });
                            });

                            attrIt("render role", 'role', 'foo');
                        });
                    });

                    describe("static roles", function() {
                        function makeSuite(role) {
                            describe(role, function() {
                                var undesiredAttrs = [
                                        'aria-hidden',
                                        'aria-disabled',
                                        'aria-label',
                                        'aria-expanded',
                                        'data-blerg'
                                    ],
                                    i, len, attr;

                                function shouldntHaveIt(attr) {
                                    describe(attr, function() {
                                        it("should not render on main el", function() {
                                            expect(dom.hasAttribute(attr)).toBe(false);
                                        });

                                        it("should not render on wrap el", function() {
                                            expect(wrapDom.hasAttribute(attr)).toBe(false);
                                        });
                                    });
                                }

                                beforeEach(function() {
                                    makeC({
                                        ariaRole: role,
                                        ariaLabel: 'foo',
                                        region: 'north',
                                        ariaAttributes: {
                                            'data-foo': 'bar'
                                        },
                                        ariaRenderAttributes: {
                                            'data-blerg': 'qux'
                                        }
                                    });
                                });

                                describe("role", function() {
                                    attrIt("render " + role + " role", 'role', role);
                                });

                                for (i = 0, len = undesiredAttrs.length; i < len; i++) {
                                    attr = undesiredAttrs[i];

                                    shouldntHaveIt(attr);
                                }
                            });
                        }

                        makeSuite('presentation');
                        makeSuite('document');
                    });

                    describe("widget roles", function() {
                        function shouldHaveIt(attr, value) {
                            describe(attr, function() {
                                attrIt('render ' + attr, attr, value);
                            });
                        }

                        describe("collapsible component", function() {
                            var attr, value,
                                desiredAttrs = {
                                    role: 'throbbe',
                                    'aria-hidden': 'false',
                                    'aria-disabled': 'false',
                                    'aria-label': 'sploosh!',
                                    'aria-expanded': 'true'
                                };

                            beforeEach(function() {
                                makeC({
                                    ariaRole: 'throbbe',
                                    ariaLabel: 'sploosh!',
                                    collapsible: true
                                });
                            });

                            for (attr in desiredAttrs) {
                                value = desiredAttrs[attr];

                                shouldHaveIt(attr, value);
                            }

                            it("should null ariaRenderAttributes", function() {
                                expect(c.ariaRenderAttributes).toBe(null);
                            });
                        });

                        describe("collapsible panel", function() {
                            var attr, value,
                                desiredAttrs = {
                                    role: 'foo',
                                    'aria-hidden': 'false',
                                    'aria-disabled': 'false',
                                    'aria-label': 'frogg',
                                    'aria-expanded': 'true',
                                    'data-baz': 'qux',
                                    'data-fred': 'frob'
                                };

                            beforeEach(function() {
                                makeC({
                                    collapsible: true,
                                    ariaRole: 'foo',
                                    ariaLabel: 'frogg',
                                    ariaAttributes: {
                                        'data-baz': 'qux'
                                    },
                                    ariaRenderAttributes: {
                                        'data-fred': 'frob'
                                    }
                                });
                            });

                            for (attr in desiredAttrs) {
                                value = desiredAttrs[attr];

                                shouldHaveIt(attr, value);
                            }

                            it("should null ariaRenderAttributes", function() {
                                expect(c.ariaRenderAttributes).toBe(null);
                            });
                        });
                    });

                    describe("component state", function() {
                        describe("hidden", function() {
                            beforeEach(function() {
                                makeC({
                                    ariaRole: 'hidden-test',
                                    hidden: true
                                });
                            });

                            attrIt('render aria-hidden', 'aria-hidden', 'true');
                        });

                        describe("disabled", function() {
                            beforeEach(function() {
                                makeC({
                                    ariaRole: 'disabled-test',
                                    disabled: true
                                });
                            });

                            attrIt('render aria-disabled', 'aria-disabled', 'true');
                        });

                        describe("collapsed", function() {
                            beforeEach(function() {
                                makeC({
                                    ariaRole: 'expanded-test',
                                    collapsible: true,
                                    collapsed: true
                                });
                            });

                            attrIt('render aria-expanded', 'aria-expanded', 'false');
                        });

                        describe("expanded", function() {
                            beforeEach(function() {
                                makeC({
                                    ariaRole: 'expanded-test',
                                    collapsible: true,
                                    collapsed: false
                                });
                            });

                            attrIt('render aria-expanded', 'aria-expanded', 'true');
                        });
                    });
                });
            }

            makeAttrSuite("on main el", {
                childEls: ['wrapEl']
            });

            makeAttrSuite("on child el", {
                ariaUsesMainElement: false,
                ariaEl: 'wrapEl',
                childEls: ['wrapEl']
            });
        });
    });
});
