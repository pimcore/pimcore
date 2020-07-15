topSuite("Ext.button.Button",
    ['Ext.Panel', 'Ext.button.Split', 'Ext.form.Label', 'Ext.app.ViewController',
     'Ext.app.ViewModel'],
function() {
    var proto = Ext.button.Button.prototype,
        button;

    function clickIt(event) {
        jasmine.fireMouseEvent(button.el.dom, event || 'click');
    }

    function destroyButton() {
        if (button) {
            button.destroy();
        }

        button = null;
    }

    function makeButton(config) {
        button = new Ext.button.Button(Ext.apply({
            text: 'Button'
        }, config));

        return button;
    }

    function createButton(config) {
        config = Ext.apply({
            renderTo: document.body
        }, config);

        return makeButton(config);
    }

    afterEach(destroyButton);

    describe("alternate class name", function() {
        it("should have Ext.Button as the alternate class name", function() {
            expect(Ext.button.Button.prototype.alternateClassName).toEqual("Ext.Button");
        });

        it("should allow the use of Ext.Button", function() {
            expect(Ext.Button).toBeDefined();
        });
    });

    it("should be isButton", function() {
        expect(proto.isButton).toBeTruthy();
    });

    it("should not be hidden", function() {
        expect(proto.hidden).toBeFalsy();
    });

    it("should not be disabled", function() {
        expect(proto.disabled).toBeFalsy();
    });

    it("should not be pressed", function() {
        expect(proto.pressed).toBeFalsy();
    });

    it("should not enableToggle", function() {
        expect(proto.enableToggle).toBeFalsy();
    });

    it("should have a menuAlign", function() {
        expect(proto.menuAlign).toEqual('tl-bl?');
    });

    it("should have a clickEvent", function() {
        expect(proto.clickEvent).toEqual('click');
    });

    it("should handleMouseEvents", function() {
        expect(proto.handleMouseEvents).toBeTruthy();
    });

    it("should have a tooltipType", function() {
        expect(proto.tooltipType).toEqual('qtip');
    });

    it("should have a baseCls", function() {
        expect(proto.baseCls).toEqual('x-btn');
    });

    it("should return a renderTpl", function() {
        expect(proto.renderTpl).toBeDefined();
    });

    it("should have a scale", function() {
        expect(proto.scale).toEqual('small');
    });

    it("should have a ui", function() {
        expect(proto.ui).toEqual('default');
    });

    it("should have a arrowAlign", function() {
        expect(proto.arrowAlign).toEqual('right');
    });

    describe("initComponent", function() {
        describe("toggleGroup", function() {
            it("if defined, it should enableToggle", function() {
                makeButton({
                    toggleGroup: 'testgroup'
                });

                expect(button.enableToggle).toBeTruthy();
            });
        });

        describe("html config", function() {
            it("should use the html config if specified", function() {
                button = new Ext.button.Button({
                    html: 'Foo'
                });
                expect(button.text).toBe('Foo');
            });

            it("should give precedence to the text config if both are specified", function() {
                makeButton({
                    html: 'Foo',
                    text: 'Bar'
                });
                expect(button.text).toBe('Bar');
            });
        });
    });

    describe("border", function() {
        it("should respect an explicit border cfg", function() {
            makeButton({
                border: false
            });
            var p = new Ext.panel.Panel({
                items: button
            });

            expect(button.border).toBe(false);

            p.destroy();
        });
    });

    describe("setUI", function() {
        beforeEach(function() {
            makeButton({
                text: 'Foo'
            });
            button.render(Ext.getBody());
            button.setUI('custom');
        });
        it("should remove x-btn-default-small class from main button element", function() {
            expect(button.el).not.toHaveCls('x-btn-default-small');
        });

        it("should add x-btn-custom-small class to main button element", function() {
            expect(button.el).toHaveCls('x-btn-custom-small');
        });

        it("should remove x-btn-wrap-default-small class from btnWrap", function() {
            expect(button.btnWrap).not.toHaveCls('x-btn-wrap-default-small');
        });

        it("should add x-btn-wrap-custom-small class to btnWrap", function() {
            expect(button.btnWrap).toHaveCls('x-btn-wrap-custom-small');
        });

        it("should remove x-btn-button-default-small class from btnEl", function() {
            expect(button.btnEl).not.toHaveCls('x-btn-button-default-small');
        });

        it("should add x-btn-button-custom-small class to btnEl", function() {
            expect(button.btnEl).toHaveCls('x-btn-button-custom-small');
        });

        it("should remove x-btn-icon-el-default-small class from btnIconEl", function() {
            expect(button.btnIconEl).not.toHaveCls('x-btn-icon-el-default-small');
        });

        it("should add x-btn-icon-el-custom-small class to btnIconEl", function() {
            expect(button.btnIconEl).toHaveCls('x-btn-icon-el-custom-small');
        });

        it("should remove x-btn-inner-default-small class from btnInnerEl", function() {
            expect(button.btnInnerEl).not.toHaveCls('x-btn-inner-default-small');
        });

        it("should add x-btn-inner-custom-small class to btnInnerEl", function() {
            expect(button.btnInnerEl).toHaveCls('x-btn-inner-custom-small');
        });
    });

    describe("setText", function() {
        it("should be able to set the text before rendering", function() {
            makeButton({
                text: 'Foo'
            });
            button.setText('Bar');
            button.render(Ext.getBody());
            expect(button.btnInnerEl.dom).hasHTML('Bar');
        });

        it("should set the text after rendering", function() {
            makeButton({
                text: 'Foo',
                renderTo: Ext.getBody()
            });
            button.setText('Bar');
            expect(button.btnInnerEl.dom).hasHTML('Bar');
        });

        it("should have a visible btnInnerEl if the text is empty and there is no icon", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: ''
            });

            expect(button.btnInnerEl.isVisible()).toBe(true);
        });

        it("should show the btnInnerEl if text is not empty", function() {
            makeButton({
                renderTo: Ext.getBody(),
                icon: 'resources/images/foo.gif',
                text: ''
            });
            // inner el starts off hidden because we have an icon
            expect(button.btnInnerEl.isVisible()).toBe(false);
            button.setText('Bar');
            expect(button.btnInnerEl.dom).hasHTML('Bar');
            expect(button.btnInnerEl.isVisible()).toBe(true);
        });

        it("should hide the btnInnerEl if text is empty and there is an icon", function() {
            makeButton({
                renderTo: Ext.getBody(),
                icon: 'resources/images/foo.gif',
                text: 'Foo'
            });
            // inner el starts off visible because we initially rendered with text
            expect(button.btnInnerEl.isVisible()).toBe(true);
            button.setText('');
            expect(button.btnInnerEl.isVisible()).toBe(false);
        });

        it("should not hide the btnInnerEl if text is emtpy and there is no icon", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: 'Foo'
            });
            expect(button.btnInnerEl.isVisible()).toBe(true);
            button.setText('');
            expect(button.btnInnerEl.isVisible()).toBe(true);
        });

        it("should render with a x-btn-text class on the btnEl when configured with text", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: 'Foo'
            });

            expect(button.btnEl).toHaveCls('x-btn-text');
        });

        it("should not render the x-btn-no-text class on the btnEl when configured with text", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: 'Foo'
            });

            expect(button.btnEl).not.toHaveCls('x-btn-no-text');
        });

        it("should not have a x-btn-text class on the btnEl when not configured with text", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: ''
            });

            expect(button.btnEl).not.toHaveCls('x-btn-text');
        });

        it("should have a x-btn-no-text class on the btnEl when not configured with text", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: ''
            });

            expect(button.btnEl).toHaveCls('x-btn-no-text');
        });

        it("should add the x-btn-text class and remove the x-btn-no-text class when setting the text", function() {
             makeButton({
                 renderTo: Ext.getBody(),
                 text: ''
            });

            button.setText('Foo');
            expect(button.btnEl).toHaveCls('x-btn-text');
            expect(button.btnEl).not.toHaveCls('x-btn-no-text');
        });

        it("should remove the x-btn-text class and add the x-btn-no-text class when setting empty text", function() {
            makeButton({
                renderTo: Ext.getBody(),
                text: 'Foo'
            });

            button.setText('');
            expect(button.btnEl).not.toHaveCls('x-btn-text');
            expect(button.btnEl).toHaveCls('x-btn-no-text');
        });

        it("should render a non breaking space when the text passed is empty", function() {
            makeButton({
                text: 'Foo',
                renderTo: Ext.getBody()
            });
            button.setText('');
            expect(button.btnInnerEl.dom.innerHTML.length).toBeGreaterThan(0);
        });

        it("should fire the textchange event", function() {
            var btn, old, newText;

            makeButton({
                text: 'Foo',
                renderTo: Ext.getBody()
            });
            button.on('textchange', function(a1, a2, a3) {
                btn = a1;
                old = a2;
                newText = a3;
            });
            button.setText('Bar');
            expect(btn).toBe(button);
            expect(old).toBe('Foo');
            expect(newText).toBe('Bar');
        });

        it("should not fire the textchange event if the text doesn't change", function() {
            var called = false;

            makeButton({
                text: 'Foo',
                renderTo: Ext.getBody()
            });
            button.on('textchange', function() {
                called = true;
            });
            button.setText('Foo');
            expect(called).toBe(false);
        });
    });

    describe("setIcon", function() {
        var fooIcon = 'resources/images/foo.gif',
            barIcon = 'resources/images/bar.gif';

        it("should be able to set the icon before rendering", function() {
            makeButton({
                icon: fooIcon
            });
            button.setIcon(barIcon);
            button.render(Ext.getBody());
            expect(button.btnIconEl.dom.style.backgroundImage.indexOf('bar')).toBeGreaterThan(-1);
        });

        it("should set the icon after rendering", function() {
            makeButton({
                icon: fooIcon,
                renderTo: Ext.getBody()
            });
            button.setIcon(barIcon);
            expect(button.btnIconEl.dom.style.backgroundImage.indexOf('bar')).toBeGreaterThan(-1);
        });

        it("should set the icon after rendering (no initial icon)", function() {
            makeButton({
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(false);
            button.setIcon(barIcon);
            expect(button.btnIconEl.dom.style.backgroundImage.indexOf('bar')).toBeGreaterThan(-1);
            expect(button.btnIconEl.isVisible()).toBe(true);
        });

        it("should unset the icon after rendering", function() {
            makeButton({
                icon: fooIcon,
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(true);
            button.setIcon(null);
            expect(button.btnIconEl.dom.style.backgroundImage.indexOf('foo')).toBe(-1);
            expect(button.btnIconEl.isVisible()).toBe(false);
        });

        it("should fire the iconchange event", function() {
            var btn, old, newIcon;

            makeButton({
                icon: fooIcon,
                renderTo: Ext.getBody()
            });
            button.on('iconchange', function(a1, a2, a3) {
                btn = a1;
                old = a2;
                newIcon = a3;
            });
            button.setIcon(barIcon);
            expect(btn).toBe(button);
            expect(old).toBe(fooIcon);
            expect(newIcon).toBe(barIcon);
        });

        it("should not fire the iconchange event if the icon doesn't change", function() {
            var called = false;

            makeButton({
                icon: fooIcon,
                renderTo: Ext.getBody()
            });
            button.on('iconchange', function() {
                called = true;
            });
            button.setIcon(fooIcon);
            expect(called).toBe(false);
        });

        it("should switch from using glyph to icon", function() {
            makeButton({
                glyph: 'x48@FontAwesome',
                renderTo: Ext.getBody()
            });

            // Hex 48 is "H". Must switch to using that with no background image
            expect(button.btnIconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(button.btnIconEl.dom.innerHTML).toBe('H');

            button.setIcon('resources/images/foo.gif');

            // No glyph character
            expect(button.btnIconEl.dom.innerHTML).toBe('');

            // iconEl must use the image as the background image.
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(button.btnIconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);
        });

        it("should switch from using iconCls to icon", function() {
            makeButton({
                iconCls: 'foo-icon-class',
                renderTo: Ext.getBody()
            });

            // iconEl must use the specified icon class
            expect(button.btnIconEl.hasCls('foo-icon-class')).toBe(true);

            button.setIcon('resources/images/foo.gif');

            // Icon class must be gone
            expect(button.btnIconEl.hasCls('foo-icon-class')).toBe(false);

            // iconEl must use the image as the background image
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(button.btnIconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);
        });
    });

    describe("setIconCls", function() {
        it("should be able to set the iconCls before rendering", function() {
            makeButton({
                iconCls: 'Foo'
            });
            button.setIconCls('Bar');
            button.render(Ext.getBody());
            expect(button.btnIconEl.hasCls('Bar')).toBe(true);
        });

        it("should set the iconCls after rendering", function() {
            makeButton({
                iconCls: 'Foo',
                renderTo: Ext.getBody()
            });
            button.setIconCls('Bar');
            expect(button.btnIconEl.hasCls('Bar')).toBe(true);
        });

        it("should set the iconCls after rendering (no initial iconCls)", function() {
            makeButton({
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(false);
            button.setIconCls('Bar');
            expect(button.btnIconEl.hasCls('Bar')).toBe(true);
            expect(button.btnIconEl.isVisible()).toBe(true);
        });

        it("should unset the iconCls after rendering", function() {
            makeButton({
                iconCls: 'Foo',
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(true);
            button.setIconCls(null);
            expect(button.btnIconEl.hasCls('Foo')).toBe(false);
            expect(button.btnIconEl.isVisible()).toBe(false);
        });

        it("should fire the iconchange event", function() {
            var btn, old, newIcon;

            makeButton({
                iconCls: 'Foo',
                renderTo: Ext.getBody()
            });
            button.on('iconchange', function(a1, a2, a3) {
                btn = a1;
                old = a2;
                newIcon = a3;
            });
            button.setIconCls('Bar');
            expect(btn).toBe(button);
            expect(old).toBe('Foo');
            expect(newIcon).toBe('Bar');
        });

        it("should not fire the iconchange event if the iconCls doesn't change", function() {
            var called = false;

            makeButton({
                iconCls: 'Foo',
                renderTo: Ext.getBody()
            });
            button.on('iconchange', function() {
                called = true;
            });
            button.setIconCls('Foo');
            expect(called).toBe(false);
        });

        it("should switch from using glyph to iconCls", function() {
            makeButton({
                glyph: 'x48@FontAwesome',
                renderTo: Ext.getBody()
            });

            // Hex 48 is "H". Must switch to using that with no background image
            expect(button.btnIconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(button.btnIconEl.dom.innerHTML).toBe('H');

            button.setIconCls('foo-icon-class');

            // No glyph character
            expect(button.btnIconEl.dom.innerHTML).toBe('');

            // iconEl must use the specified icon class
            expect(button.btnIconEl.hasCls('foo-icon-class')).toBe(true);
        });
        it("should switch from using glyph to icon", function() {
            makeButton({
                glyph: 'x48@FontAwesome',
                renderTo: Ext.getBody()
            });

            // Hex 48 is "H". Must switch to using that with no background image
            expect(button.btnIconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(button.btnIconEl.dom.innerHTML).toBe('H');

            button.setIcon('resources/images/foo.gif');

            // No glyph character
            expect(button.btnIconEl.dom.innerHTML).toBe('');

            // iconEl must use the image as the background image
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(button.btnIconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);
        });
    });

    describe("setGlyph", function() {
        it("should be able to set the glyph before rendering", function() {
            makeButton({
                glyph: 65
            });
            button.setGlyph(66);
            button.render(Ext.getBody());
            expect(button.btnIconEl.dom.innerHTML).toBe('B');
        });

        it("should set the glyph after rendering", function() {
            makeButton({
                glyph: 65,
                renderTo: Ext.getBody()
            });
            button.setGlyph(66);
            expect(button.btnIconEl.dom.innerHTML).toBe('B');
        });

        it("should set the glyph after rendering (no initial glyph)", function() {
            makeButton({
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(false);
            button.setGlyph(66);
            expect(button.btnIconEl.dom.innerHTML).toBe('B');
            expect(button.btnIconEl.isVisible()).toBe(true);
        });

        it("should unset the glyph after rendering", function() {
            makeButton({
                glyph: 65,
                renderTo: Ext.getBody()
            });
            expect(button.btnIconEl.isVisible()).toBe(true);
            button.setGlyph(null);
            expect(button.btnIconEl.dom.innerHTML).toBe('');
            expect(button.btnIconEl.isVisible()).toBe(false);
        });

        it("should fire the glyphchange event", function() {
            var btn, old, newGlyph;

            makeButton({
                glyph: 65,
                renderTo: Ext.getBody()
            });
            button.on('glyphchange', function(a1, a2, a3) {
                btn = a1;
                newGlyph = a2;
                old = a3;
            });
            button.setGlyph(66);
            expect(btn).toBe(button);
            expect(old).toBe(65);
            expect(newGlyph).toBe(66);
        });

        it("should switch from using icon to glyph", function() {
            makeButton({
                renderTo: Ext.getBody(),
                icon: 'resources/images/foo.gif',
                text: ''
            });

            // iconEl must use the image as the background image
            // Some browsers quote the url value, some don't. Remove quotes.
            expect(Ext.String.endsWith(button.btnIconEl.getStyle('background-image').replace(/\"/g, ''), 'resources/images/foo.gif)')).toBe(true);

            // Hex 48 is "H". Must switch to using that with no background image
            button.setGlyph('x48@FontAwesome');
            expect(button.btnIconEl.getStyle('background-image')).toBe('none');
            expect(button.btnIconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(button.btnIconEl.dom.innerHTML).toBe('H');
        });

        it("should switch from using iconCls to glyph", function() {
            makeButton({
                renderTo: Ext.getBody(),
                iconCls: 'foo-icon-class',
                text: ''
            });

            // iconEl must use the image as the background image
            expect(button.btnIconEl.hasCls('foo-icon-class')).toBe(true);

            // Hex 48 is "H". Must switch to using that with no background image
            button.setGlyph('x48@FontAwesome');
            expect(button.btnIconEl.getStyle('background-image')).toBe('none');
            expect(button.btnIconEl.getStyle('font-family')).toBe('FontAwesome');
            expect(button.btnIconEl.dom.innerHTML).toBe('H');
        });
    });

    describe("setting the url", function() {
        function expectHref(href) {
            expect(button.getEl().dom.href.indexOf(href)).toBeGreaterThan(-1);
        }

        function expectEmptyHref() {
            expect(button.getEl().dom.href).toBe('');
        }

        var sencha = 'http://sencha.com',
            target = '_blank';

        describe("setHref", function() {
            function expectEmptyTarget() {
                expect(button.getEl().dom.href).toBe('');
            }

            function expectHrefTarget(target) {
                expect(button.getEl().dom.target).toBe(target);
            }

            describe("before render", function() {
                it("should be able to set the href before rendered", function() {
                    makeButton({
                        hrefTarget: target
                    });
                    button.setHref(sencha);
                    button.render(Ext.getBody());
                    expectHref('sencha.com');
                    expectHrefTarget(target);
                });

                it("should overwrite a configured href", function() {
                    makeButton({
                        href: 'http://foo.com',
                        hrefTarget: target
                    });
                    button.setHref(sencha);
                    button.render(Ext.getBody());
                    expectHref('sencha.com');
                    expectHrefTarget(target);
                });

                it("should clear a configured href", function() {
                    makeButton({
                        href: sencha,
                        hrefTarget: target
                    });
                    button.setHref('');
                    button.render(Ext.getBody());
                    expectEmptyHref();
                    expectEmptyTarget();
                });

                it("should not set if configured disabled: true", function() {
                    makeButton({
                        disabled: true
                    });
                    button.setHref('');
                    button.render(Ext.getBody());
                    expectEmptyHref();
                });
            });

            describe("after render", function() {
                it("should set if no href is initially configured", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        hrefTarget: target
                    });
                    button.setHref(sencha);
                    expectHref('sencha.com');
                    expectHrefTarget(target);
                });

                it("should overwrite a configured href", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: 'http://foo.com',
                        hrefTarget: target
                    });
                    button.setHref(sencha);
                    expectHref('sencha.com');
                    expectHrefTarget(target);
                });

                it("should clear a configured href", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        hrefTarget: target
                    });
                    button.setHref('');
                    expectEmptyHref();
                    expectEmptyTarget();
                });

                it("should not set the href on the element if disabled", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        hrefTarget: target,
                        disabled: true
                    });
                    button.setHref(sencha);
                    expectEmptyHref();
                    expectEmptyTarget();
                });
            });
        });

        describe("setParams", function() {
            function getQueryString() {
                var href = button.getEl().dom.href,
                    parts;

                if (href) {
                    parts = href.split('?');

                    if (parts.length === 2) {
                        return Ext.Object.fromQueryString(parts[1]);
                    }
                }

                return {};
            }

            // Since the url is string encoded we lose any type information
            describe("before render", function() {
                it("should be able to set the params", function() {
                    makeButton({
                        href: sencha
                    });
                    button.setParams({
                        foo: 1
                    });
                    button.render(Ext.getBody());
                    expect(getQueryString()).toEqual({
                        foo: '1'
                    });
                });

                it("should overwrite configured params", function() {
                    makeButton({
                        href: sencha,
                        params: {
                            foo: 1
                        }
                    });
                    button.setParams({
                        bar: 1
                    });
                    button.render(Ext.getBody());
                    expect(getQueryString()).toEqual({
                        bar: '1'
                    });
                });

                it("should clear params", function() {
                    makeButton({
                        href: sencha,
                        params: {
                            foo: 1
                        }
                    });
                    button.setParams(null);
                    button.render(Ext.getBody());
                    expect(getQueryString()).toEqual({});
                });
            });

            describe("after render", function() {
                it("should set if no params were configured", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha
                    });
                    button.setParams({
                        foo: 1
                    });
                    expect(getQueryString()).toEqual({
                        foo: '1'
                    });
                });

                it("should overwrite existing params", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        params: {
                            foo: 1
                        }
                    });
                    button.setParams({
                        bar: 1
                    });
                    expect(getQueryString()).toEqual({
                        bar: '1'
                    });
                });

                it("should clear params", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        params: {
                            foo: 1
                        }
                    });
                    button.setParams(null);
                    expect(getQueryString()).toEqual({});
                });
            });

            describe("with href", function() {
                it("should set params if the button href is set later", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        params: {
                            foo: 1
                        }
                    });
                    expect(getQueryString()).toEqual({});
                    button.setHref(sencha);
                    expect(getQueryString()).toEqual({
                        foo: '1'
                    });
                });

                it("should not set params if the url is cleared", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        params: {
                            foo: 1
                        }
                    });
                    button.setHref(null);
                    expect(getQueryString()).toEqual({});
                });
            });

            describe("baseParams", function() {
                it("should append any baseParams to the params", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        params: {
                            foo: 1
                        },
                        baseParams: {
                            bar: 1
                        }
                    });
                    expect(getQueryString()).toEqual({
                        foo: '1',
                        bar: '1'
                    });
                });

                it("should should favour the params", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        href: sencha,
                        params: {
                            foo: 2
                        },
                        baseParams: {
                            foo: 1
                        }
                    });
                    expect(getQueryString()).toEqual({
                        foo: '2'
                    });
                });
            });
        });
    });

    describe("getActionEl", function() {
        beforeEach(function() {
            makeButton({ renderTo: Ext.getBody() });
        });

        it("should return the el", function() {
            expect(button.getActionEl()).toEqual(button.el);
        });
    });

    describe("beforerender", function() {
        it("should cancel rendering if beforerender returns false", function() {
            var count = 0;

            makeButton({
                renderTo: Ext.getBody(),
                listeners: {
                    beforerender: function() {
                        count++;

                        return false;
                    },
                    render: function() {
                        count += 2;
                    },
                    afterrender: function() {
                        count += 3;
                    }
                }
            });

            expect(count).toBe(1);
            expect(button.el).toBeUndefined();
            expect(button.rendered).toBeFalsy();
        });

        // TODO: Add more assertions like these based on the other setters. Rather than picking at the internals it
        //       would probably make sense to do assertions based on comparing the markup of two theoretically identical
        //       buttons, one created directly and one that used the setters.
        it("should be possible to set the iconCls within a beforerender listener", function() {
            makeButton({
                renderTo: Ext.getBody(),
                listeners: {
                    beforerender: function(btn) {
                        btn.setIconCls('my-icon');
                    }
                }
            });

            // Not the most precise assertion but it covers a large number of possible errors
            expect(button.el.down('.my-icon')).toBeTruthy();
        });

        it("should be possible to set the text within a beforerender listener", function() {
            makeButton({
                renderTo: Ext.getBody(),
                listeners: {
                    beforerender: function(btn) {
                        btn.setText('text');
                    }
                }
            });

            // This is perhaps a little over specific
            expect(button.btnInnerEl.dom.innerHTML).toBe('text');
        });
    });

    describe("menu", function() {
        it("should not include menu descendant items in its CQ children if the &gt; combinator is used", function() {
            var queryResult;

            makeButton({
                menu: [{
                    text: 'Foo',
                    menu: [{
                        text: 'Bar',
                        menu: [{
                            text: 'Bletch'
                        }]
                    }]
                }]
            });

            // Child only query should just return the menu
            queryResult = button.query('>*');
            expect(queryResult.length).toBe(1);
            expect(queryResult[0] === button.menu).toBe(true);

            // Deep query should return the menu and its descendants.
            queryResult = button.query('*');

            // Yes. Six:
            // Top menu, its Foo item, Foo's Menu, the Bar item, Bars menu, and the Bletch item.
            expect(queryResult.length).toBe(6);
        });

        it("should accept a menu configuration", function() {
            makeButton({
                menu: {}
            });
            expect(button.menu.isMenu).toBe(true);
        });

        it("should destroy the menu on destroy", function() {
            var menu = new Ext.menu.Menu();

            makeButton({
                menu: menu
            });
            button.destroy();
            expect(menu.destroyed).toBe(true);
            menu = null;
        });

        it("should not destroy the menu with destroyMenu: false", function() {
            var menu = new Ext.menu.Menu();

            makeButton({
                destroyMenu: false,
                menu: menu
            });
            button.destroy();
            expect(menu.destroyed).toBeFalsy();
            menu.destroy();
            menu = null;
        });

        it("should show menu on click", function() {
            var menu;

            runs(function() {
                menu = new Ext.menu.Menu({
                    shadow: false,
                    items: {
                        text: 'An item'
                    }
                });
                makeButton({
                    renderTo: Ext.getBody(),
                    menu: menu
                });

                // Menu's timer before which it won't show after a hide
                menu.menuClickBuffer = 1;

                // Opening the menu with mouse does not focus it
                clickIt();
                expect(menu.isVisible()).toBe(true);
                expect(menu.containsFocus).toBeFalsy();

                // Mousedown outside the menu hides it
                clickIt("mousedown");
                expect(menu.isVisible()).toBe(false);
                clickIt('mouseup');
            });

            // Wait for 1ms hide timer set above to expire
            waits(5);

            // Now the menu should be willing to show again
            runs(function() {
                // Opening the menu with down arrow focuses it
                jasmine.syncPressArrowKey(button, 'down');
                expect(menu.isVisible()).toBe(true);
            });

            waitsFor(function() {
                return menu.containsFocus;
            });
        });

        it("should not show menu on click if the menu is empty", function() {
            var menu = new Ext.menu.Menu();

            makeButton({
                renderTo: Ext.getBody(),
                menu: menu
            });

            clickIt();
            expect(menu.isVisible()).toBe(false);
        });

        it("should show menu when showMenu is called, even if empty", function() {
            var menu = new Ext.menu.Menu({
                shadow: false
            });

            makeButton({
                renderTo: Ext.getBody(),
                menu: menu
            });

            button.showMenu();
            expect(menu.isVisible()).toBe(true);
        });

        it("should be able to access the owner during construction", function() {
            var owner;

            Ext.define('spec.SubMenu', {
                extend: 'Ext.menu.Menu',
                alias: 'widget.submenu',

                initComponent: function() {
                    owner = this.getRefOwner();
                    this.callParent();
                }
            });

            makeButton({
                renderTo: Ext.getBody(),
                menu: {
                    xtype: 'submenu',
                    items: [{
                        text: 'A'
                    }]
                }
            });
            expect(owner).toBe(button);

            Ext.undefine('spec.SubMenu');
        });

        describe("Hiding on scroll", function() {
            // See EXTJS-14754.
            var ctn, menu;

            beforeEach(function() {
                makeButton({
                    xtype: 'button',
                    text: 'Menu Button',
                    y: 300,
                    menu: {
                        items: [{
                            text: '1'
                        }, {
                            text: '2'
                        }]
                    }
                });

                menu = button.menu;

                ctn = new Ext.container.Container({
                    autoScroll: true,
                    height: 400,
                    renderTo: document.body,
                    items: [button, {
                        xtype: 'label',
                        text: 'The End.',
                        y: 2000
                    }]
                });

                clickIt();
            });

            afterEach(function() {
                Ext.destroy(ctn);
                ctn = null;
            });

            it("should hide on scroll", function() {
                // Let's make sure before we start that the menu is positioned correctly.
                expect(menu.getY() - button.getHeight()).toBe(button.getY());

                // Now, let's scroll down.
                ctn.scrollable.scrollTo(0, 200);

                waitsFor(function() {
                    // Scrolling should cause menu hide;
                    return menu.isVisible() === false;
                });
            });
        });

        describe("when destroying its owner", function() {
            var menu;

            beforeEach(function() {
                menu = new Ext.menu.Menu();

                makeButton({
                    menu: menu
                });

                button.destroy();
            });

            afterEach(function() {
                menu = null;
            });

            it("should work", function() {
                expect(menu.destroyed).toBe(true);
            });

            it("should cleanup its menu reference", function() {
                expect(button.menu).toBe(null);
            });
        });

        describe("setMenu", function() {
            // See EXTJSIV-11433, EXTJSIV-11837.
            var menuCfg = {
                    defaultAlign: 'c',
                    menu: {
                        items: [{
                            text: 'Level 2'
                        }]
                    }
                },
                menuCmp, mainMenu;

            beforeEach(function() {
                mainMenu = new Ext.menu.Menu({
                    id: 'lily'
                });

                menuCmp = new Ext.menu.Menu({
                    id: 'rupert'
                });

                makeButton({
                    menu: mainMenu,
                    renderTo: Ext.getBody()
                });
            });

            afterEach(function() {
                Ext.destroy(mainMenu, menuCmp);
                mainMenu = menuCmp = null;
            });

            describe("setting a menu", function() {
                it("should accept a menu component as an argument", function() {
                    button.setMenu(menuCmp);
                    expect(button.menu.isMenu).toBe(true);
                    expect(button.menu).toBe(menuCmp);
                });

                it("should accept a menu config as an argument", function() {
                    button.setMenu(menuCfg);
                    expect(button.getMenu().isMenu).toBe(true);
                    expect(button.getMenu().defaultAlign).toBe('c');
                });

                it("should accept a menu id as an argument", function() {
                    // Pass `false` to not destroy the previous set menu when setting the new one.
                    button.setMenu('rupert', false);
                    expect(button.menu).toBe(menuCmp);
                });

                it("should poke the split classes onto the btnWrap element when the new menu is set", function() {
                    var btn = new Ext.button.Button({
                        renderTo: Ext.getBody()
                    });

                    btn.setMenu(menuCmp);
                    expect(btn.btnWrap).toHaveCls('x-btn-arrow');
                    expect(btn.btnWrap).toHaveCls('x-btn-arrow-right');

                    btn.destroy();
                    btn = null;
                });
            });

            describe("unsetting a menu", function() {
                it("should null out the button's menu property", function() {
                    button.setMenu(null);
                    expect(button.menu).toBe(null);
                });

                it("should remove the split classes on the btnWrap element when the menu is unset", function() {
                    button.setMenu(null);
                    expect(button.btnWrap).not.toHaveCls('x-btn-arrow');
                    expect(button.btnWrap).not.toHaveCls('x-btn-arrow-right');
                });
            });

            describe("destroying previous set menu", function() {
                describe("when setting", function() {
                    it("should destroy the previous set menu when setting the new one by default", function() {
                        button.setMenu(menuCmp);
                        expect(mainMenu.destroyed).toBe(true);
                    });

                    it("should not destroy the previous set menu when setting the new one when passing `false`", function() {
                        button.setMenu(menuCmp, false);
                        expect(mainMenu.destroyed).toBeFalsy();
                    });

                    it("should not destroy the previous set menu when destroyMenu instance property is `false`", function() {
                        button.destroyMenu = false;
                        button.setMenu(menuCmp);
                        expect(mainMenu.destroyed).toBeFalsy();
                    });
                });

                describe("when unsetting", function() {
                    it("should destroy the current menu", function() {
                        button.setMenu(null);
                        expect(mainMenu.destroyed).toBe(true);
                    });

                    it("should not destroy the current menu if passed `false`", function() {
                        button.setMenu(null, false);
                        expect(mainMenu.destroyed).toBeFalsy();
                    });

                    it("should not destroy the previous set menu when destroyMenu instance property is `false`", function() {
                        button.destroyMenu = false;
                        button.setMenu(null);
                        expect(mainMenu.destroyed).toBeFalsy();
                    });
                });
            });
        });

        describe("ARIA attributes", function() {
            var menu;

            beforeEach(function() {
                menu = new Ext.menu.Menu({
                    items: [{
                        text: 'foo'
                    }]
                });

                makeButton({
                    renderTo: Ext.getBody(),
                    menu: menu
                });
            });

            describe("aria-haspopup", function() {
                it("should render attribute", function() {
                    expect(button).toHaveAttr('aria-haspopup', 'true');
                });

                it("should remove attribute when menu is removed", function() {
                    button.setMenu(null);

                    expect(button).not.toHaveAttr('aria-haspopup');
                });

                it("should set attribute when menu is added", function() {
                    button.setMenu(null, false);
                    button.setMenu(menu);

                    expect(button).toHaveAttr('aria-haspopup', 'true');
                });
            });

            describe("aria-owns", function() {
                it("should be set to menu id", function() {
                    button.showMenu();

                    expect(button).toHaveAttr('aria-owns', menu.id);
                });

                it("should be removed when menu is removed", function() {
                    // To make sure that attribute is set
                    button.showMenu();
                    button.hideMenu();

                    button.setMenu(null);

                    expect(button).not.toHaveAttr('aria-owns');
                });

                it("should be set when menu is added", function() {
                    button.setMenu(null, false);
                    button.setMenu(menu);

                    expect(button).toHaveAttr('aria-owns', menu.id);
                });
            });
        });

        describe("keyboard interaction", function() {
            var enterSpy, downSpy;

            beforeEach(function() {
                makeButton({
                    text: 'foo',
                    menu: [{
                        text: 'item1'
                    }]
                });

                enterSpy = spyOn(button, 'onEnterKey').andCallThrough();
                downSpy  = spyOn(button, 'onDownKey').andCallThrough();

                button.render(Ext.getBody());
            });

            afterEach(function() {
                enterSpy = downSpy = null;
            });

            describe("Space key", function() {
                beforeEach(function() {
                    jasmine.pressKey(button.el, 'space');

                    waitForSpy(enterSpy);
                });

                it("should open the menu", function() {
                    expect(button.menu.isVisible()).toBe(true);
                });

                it("should stop the keydown event", function() {
                    var args = enterSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBeTruthy();
                });

                it("should return false to stop Event propagation loop", function() {
                    expect(enterSpy.mostRecentCall.result).toBe(false);
                });
            });

            describe("Enter key", function() {
                beforeEach(function() {
                    jasmine.pressKey(button.el, 'enter');

                    waitForSpy(enterSpy);
                });

                it("should open the menu", function() {
                    expect(button.menu.isVisible()).toBe(true);
                });

                it("should stop the keydown event", function() {
                    var args = enterSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBeTruthy();
                });

                it("should return false to stop Event propagation loop", function() {
                    expect(enterSpy.mostRecentCall.result).toBe(false);
                });
            });

            describe("Down arrow key", function() {
                beforeEach(function() {
                    jasmine.pressKey(button.el, 'down');

                    waitForSpy(downSpy);
                });

                it("should open the menu", function() {
                    expect(button.menu.isVisible()).toBe(true);
                });

                it("should stop the keydown event", function() {
                    var args = downSpy.mostRecentCall.args;

                    expect(args[0].stopped).toBeTruthy();
                });

                it("should return false to stop Event propagation loop", function() {
                    expect(downSpy.mostRecentCall.result).toBe(false);
                });
            });
        });
    });

    describe("tooltip", function() {
        var QTM = Ext.tip.QuickTipManager;

        beforeEach(function() {
            QTM.init();
        });

        afterEach(function() {
            button.destroy();
            button = null;
        });

        describe("configuring", function() {
            it("should set the qtip attribute", function() {
                makeButton({
                    tooltip: 'Foo',
                    renderTo: Ext.getBody()
                });
                expect(button.el.getAttribute('data-qtip')).toBe('Foo');
            });

            it("should set the title attribute", function() {
                makeButton({
                    tooltip: 'Foo',
                    tooltipType: 'title',
                    renderTo: Ext.getBody()
                });
                expect(button.el.getAttribute('title')).toBe('Foo');
            });

            it("should register with the tip manager", function() {
                var id = Ext.id(),
                    cfg = {
                        html: 'Foo'
                    },
                    o;

                spyOn(QTM, 'register').andCallFake(function(arg) {
                    o = arg;
                });
                makeButton({
                    id: id,
                    tooltip: cfg,
                    renderTo: Ext.getBody()
                });
                cfg.target = id;
                expect(o).toEqual(cfg);
            });
        });

        describe("before rendering", function() {
            it("should set the qtip attribute", function() {
                makeButton();
                button.setTooltip('Foo');
                button.render(Ext.getBody());
                expect(button.el.getAttribute('data-qtip')).toBe('Foo');
            });

            it("should set the title attribute", function() {
                makeButton({
                    tooltipType: 'title'
                });
                button.setTooltip('Foo');
                button.render(Ext.getBody());
                expect(button.el.getAttribute('title')).toBe('Foo');
            });

            it("should register with the tip manager", function() {
                var id = Ext.id(),
                    cfg = {
                        html: 'Foo'
                    },
                    o;

                spyOn(QTM, 'register').andCallFake(function(arg) {
                    o = arg;
                });
                makeButton({
                    id: id
                });
                cfg.target = id;
                button.setTooltip(cfg);
                button.render(Ext.getBody());
                expect(o).toEqual(cfg);
            });
        });

        describe("after rendering", function() {
            describe("setting the tip", function() {
                it("should set the qtip attribute", function() {
                    makeButton({
                        renderTo: Ext.getBody()
                    });
                    button.setTooltip('Foo');
                    expect(button.el.getAttribute('data-qtip')).toBe('Foo');
                });

                it("should set the title attribute", function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        tooltipType: 'title'
                    });
                    button.setTooltip('Foo');
                    expect(button.el.getAttribute('title')).toBe('Foo');
                });

                it("should register with the tip manager", function() {
                    var cfg = {
                        html: 'Foo'
                    },
                    o;

                    spyOn(QTM, 'register').andCallFake(function(arg) {
                        o = arg;
                    });
                    makeButton({
                        renderTo: Ext.getBody()
                    });
                    cfg.target = button.id;
                    button.setTooltip(cfg);
                    expect(o).toEqual(cfg);
                });
            });

            describe("clearing the tip", function() {
                it("should set the qtip attribute", function() {
                    makeButton({
                        tooltip: 'Foo',
                        renderTo: Ext.getBody()
                    });
                    button.setTooltip(null);
                    expect(button.el.getAttribute('data-qtip')).toBeFalsy();
                });

                it("should set the title attribute", function() {
                    makeButton({
                        tooltip: 'Foo',
                        renderTo: Ext.getBody(),
                        tooltipType: 'title'
                    });
                    button.setTooltip(null);
                    expect(button.el.getAttribute('title')).toBeFalsy();
                });

                it("should unregister with the tip manager", function() {
                    var cfg = {
                        html: 'Foo'
                    };

                    spyOn(QTM, 'unregister').andCallThrough();
                    makeButton({
                        tooltip: cfg,
                        renderTo: Ext.getBody()
                    });
                    button.setTooltip(null);
                    expect(QTM.unregister.mostRecentCall.args[0].id).toEqual(button.id);
                });
            });

            describe("destroying", function() {
                it("should clear the tip", function() {
                    var cfg = {
                        html: 'Foo'
                    };

                    spyOn(QTM, 'unregister').andCallThrough();
                    makeButton({
                        tooltip: cfg,
                        renderTo: Ext.getBody()
                    });
                    button.destroy();
                    expect(QTM.unregister.mostRecentCall.args[0].id).toEqual(button.id);
                });
            });
        });
    });

    describe("handler/events", function() {
        var spy;

        beforeEach(function() {
            spy = jasmine.createSpy();
        });

        afterEach(function() {
            spy = null;
        });

        function makeEventButton(cfg) {
            makeButton(Ext.apply({
                renderTo: Ext.getBody()
            }, cfg));
        }

        describe("click event", function() {
            it("should fire the click event", function() {
                makeEventButton();
                button.on('click', spy);
                clickIt();
                expect(spy).toHaveBeenCalled();
            });

            it("should pass the button and the event object", function() {
                makeEventButton();
                button.on('click', spy);
                clickIt();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(button);
                expect(args[1] instanceof Ext.event.Event).toBe(true);
            });
        });

        describe("handler", function() {
            it("should call the handler fn", function() {
                makeEventButton({
                    handler: spy
                });
                button.setHandler(spy);
                clickIt();
                expect(spy).toHaveBeenCalled();
            });

            it("should pass the button and the event object", function() {
                makeEventButton({
                    handler: spy
                });
                clickIt();
                var args = spy.mostRecentCall.args;

                expect(args[0]).toBe(button);
                expect(args[1] instanceof Ext.event.Event).toBe(true);
            });

            it("should default the scope to the button", function() {
                makeEventButton({
                    handler: spy
                });
                clickIt();
                expect(spy.mostRecentCall.object).toBe(button);
            });

            it("should use the passed scope", function() {
                var scope = {};

                makeEventButton({
                    handler: spy,
                    scope: scope
                });
                clickIt();
                expect(spy.mostRecentCall.object).toBe(scope);
            });

            it("should be able to resolve to a View Controller", function() {
                makeEventButton({
                    handler: 'doFoo',
                    renderTo: null
                });

                var ctrl = new Ext.app.ViewController();

                ctrl.doFoo = spy;
                var ct = new Ext.container.Container({
                    renderTo: Ext.getBody(),
                    controller: ctrl,
                    items: button
                });

                clickIt();
                expect(spy).toHaveBeenCalled();
                ct.destroy();
            });

            it("should not fire the handler if the click event returns false", function() {
                makeEventButton({
                    handler: spy
                });
                button.on('click', function() {
                    return false;
                });
                clickIt();
                expect(spy).not.toHaveBeenCalled();
            });

            it("should not fire the handler if the click event destroys the button", function() {
                makeEventButton({
                    handler: spy
                });
                button.on('click', function() {
                    return button.destroy();
                });
                clickIt();
                expect(spy).not.toHaveBeenCalled();
            });
        });
    });

    describe("menuAlign config", function() {
        var pos = 'br-tl';

        it("should use default menuAlign if none is given", function() {
            makeButton({
                floating: true,
                menu: {
                    plain: true,
                    items: [{
                        text: 'foo'
                    }, {
                        text: 'bar'
                    }]
                }
            });

            expect(button.menuAlign).toBe(button.self.prototype.menuAlign);
        });

        it("should use menuAlign config if given", function() {
            makeButton({
                floating: true,
                menuAlign: pos,
                menu: {
                    plain: true,
                    items: [{
                        text: 'foo'
                    }, {
                        text: 'bar'
                    }]
                },
                renderTo: Ext.getBody()
            });

            expect(button.menuAlign).toBe(pos);
            expect(button.self.prototype.menuAlign).not.toBe(pos);
        });

        it("should call alignTo() to position itself", function() {
            var ctn, menu;

            makeButton({
                menuAlign: pos,
                menu: {
                    plain: true,
                    items: [{
                        text: 'foo'
                    }, {
                        text: 'bar'
                    }]
                }
            });

            ctn = new Ext.Container({
                floating: true,
                items: button,
                renderTo: Ext.getBody()
            });

            menu = button.menu;
            spyOn(menu, 'alignTo');
            clickIt();

            expect(menu.alignTo).toHaveBeenCalledWith(button.el, pos, undefined, false, true);

            Ext.destroy(ctn);
            ctn = null;
        });
    });

    if (!Ext.supports.CSS3BorderRadius) {
        // see EXTJSIV-10376
        describe("frame", function() {
            it("should call the click listener on the wrapped table when the button is clicked", function() {
                makeButton({
                    frame: true,
                    href: '/foo',
                    renderTo: Ext.getBody(),
                    xhooks: {
                        frameTableListener: jasmine.createSpy('frameTableListener')
                    }
                });

                button.frameTable.dom.click();

                expect(button.frameTableListener).toHaveBeenCalled();
            });

            it("should call NOT the navigate method when a disabled button is clicked", function() {
                // see EXTJSIV-11276
                makeButton({
                    frame: true,
                    href: '/foo',
                    disabled: true,
                    renderTo: Ext.getBody()
                });

                spyOn(button, 'doNavigate');

                button.frameTable.dom.click();
                expect(button.doNavigate).not.toHaveBeenCalled();
            });

            it("should append any params to the url", function() {
                spyOn(Ext.button.Button.prototype, 'getHref').andCallFake(function() {
                    return null;
                });

                makeButton({
                    frame: true,
                    href: '/foo',
                    renderTo: Ext.getBody()
                });

                window.open = Ext.emptyFn;
                button.frameTable.dom.click();
                window.open = undefined; // IE8 :(

                expect(button.getHref).toHaveBeenCalled();
            });
        });
    }

    describe("arrowVisible", function() {
        describe("initial value true", function() {
            var arrowCls = 'x-btn-arrow',
                arrowClsRight = 'x-btn-arrow-right',
                operaArrowCls = 'x-opera12m-btn-arrow-right';

            describe("with menu", function() {
                it("should render with arrowCls on the buttonWrap if arrowVisible is true", function() {
                    makeButton({
                        renderTo: document.body,
                        menu: [ { text: 'fake item' }]
                    });
                    expect(button.btnWrap).toHaveCls(arrowCls);
                    expect(button.btnWrap).toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).toHaveCls(operaArrowCls);
                    }
                });

                it("should hide and show the arrow", function() {
                    makeButton({
                        renderTo: document.body,
                        menu: [ { text: 'fake item' }]
                    });
                    button.setArrowVisible(false);
                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }

                    button.setArrowVisible(true);
                    expect(button.btnWrap).toHaveCls(arrowCls);
                    expect(button.btnWrap).toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).toHaveCls(operaArrowCls);
                    }
                });

                it("should not render with arrowCls on the buttonWrap if arrowVisible is false", function() {
                    makeButton({
                        renderTo: document.body,
                        menu: [ { text: 'fake item' }],
                        arrowVisible: false
                    });
                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }
                });

                it("should remove the arrowCls if the menu is subsequently removed", function() {
                    makeButton({
                        renderTo: document.body,
                        menu: [ { text: 'fake item' }]
                    });

                    button.setMenu(null);

                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }
                });
            });

            describe("without menu", function() {
                it("should not render with arrowCls on the buttonWrap", function() {
                    makeButton({
                        renderTo: document.body
                    });
                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }
                });

                it("should not show the arrow", function() {
                    makeButton({
                        renderTo: document.body
                    });
                    button.setArrowVisible(true);
                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }
                });

                it("should add the arrowCls if a menu is subsequently added", function() {
                    makeButton({
                        renderTo: document.body
                    });

                    button.setMenu([{ text: 'fake item' }]);

                    expect(button.btnWrap).toHaveCls(arrowCls);
                    expect(button.btnWrap).toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).toHaveCls(operaArrowCls);
                    }
                });

                it("should not add the arrowCls if a menu is subsequently added, if arrowVisible is false", function() {
                    makeButton({
                        renderTo: document.body,
                        arrowVisible: false
                    });

                    button.setMenu([{ text: 'fake item' }]);

                    expect(button.btnWrap).not.toHaveCls(arrowCls);
                    expect(button.btnWrap).not.toHaveCls(arrowClsRight);

                    if (Ext.isOpera12m) {
                        expect(button.el).not.toHaveCls(operaArrowCls);
                    }
                });
            });
        });
    });

    describe("dynamic iconAlign", function() {
        it("should set the iconAlign dynamically after render", function() {
            makeButton({
                renderTo: document.body,
                iconCls: 'foo',
                text: 'Icon Align'
            });

            var btnEl = button.btnEl,
                btnIconEl = button.btnIconEl,
                btnInnerEl = button.btnInnerEl;

            expect(btnEl.first()).toBe(btnIconEl);
            expect(btnEl.last()).toBe(btnInnerEl);
            expect(btnEl).toHaveCls('x-btn-icon-left');
            expect(btnEl).not.toHaveCls('x-btn-icon-top');
            expect(btnEl).not.toHaveCls('x-btn-icon-right');
            expect(btnEl).not.toHaveCls('x-btn-icon-bottom');

            button.setIconAlign('right');

            expect(btnEl.first()).toBe(btnInnerEl);
            expect(btnEl.last()).toBe(btnIconEl);
            expect(btnEl).toHaveCls('x-btn-icon-right');
            expect(btnEl).not.toHaveCls('x-btn-icon-top');
            expect(btnEl).not.toHaveCls('x-btn-icon-left');
            expect(btnEl).not.toHaveCls('x-btn-icon-bottom');

            button.setIconAlign('top');

            expect(btnEl.first()).toBe(btnIconEl);
            expect(btnEl.last()).toBe(btnInnerEl);
            expect(btnEl).toHaveCls('x-btn-icon-top');
            expect(btnEl).not.toHaveCls('x-btn-icon-right');
            expect(btnEl).not.toHaveCls('x-btn-icon-left');
            expect(btnEl).not.toHaveCls('x-btn-icon-bottom');

            button.setIconAlign('bottom');

            expect(btnEl.first()).toBe(btnInnerEl);
            expect(btnEl.last()).toBe(btnIconEl);
            expect(btnEl).toHaveCls('x-btn-icon-bottom');
            expect(btnEl).not.toHaveCls('x-btn-icon-top');
            expect(btnEl).not.toHaveCls('x-btn-icon-right');
            expect(btnEl).not.toHaveCls('x-btn-icon-left');

            button.setIconAlign('left');

            expect(btnEl.first()).toBe(btnIconEl);
            expect(btnEl.last()).toBe(btnInnerEl);
            expect(btnEl).toHaveCls('x-btn-icon-left');
            expect(btnEl).not.toHaveCls('x-btn-icon-top');
            expect(btnEl).not.toHaveCls('x-btn-icon-right');
            expect(btnEl).not.toHaveCls('x-btn-icon-bottom');
        });
    });

    describe("layout", function() {
        var dimensions = {
                1: 'width',
                2: 'height',
                3: 'width and height'
            };

        describe("simple tests", function() {
            it("should be able to have a height of 0", function() {
                expect(function() {
                    makeButton({
                        renderTo: Ext.getBody(),
                        height: 0
                    });
                }).not.toThrow();
            });

            it("should be able to size larger after hitting a minWidth constraint", function() {
                makeButton({
                    renderTo: Ext.getBody(),
                    minWidth: 75,
                    text: 'Foo'
                });
                button.setText('Text that will stretch longer than 75px');
                expect(button.getWidth()).toBeGreaterThan(75);
            });
        });

        function makeLayoutSuite(shrinkWrap, stretch) {
            var shrinkWidth = (shrinkWrap & 1),
                shrinkHeight = (shrinkWrap & 2);

            function makeButton(config) {
                // Turn the icon green (specs don't need this, but helps when debugging)
                Ext.util.CSS.createStyleSheet('.spec-icon{background-color:green;}', 'btnSpecStyleSheet');

                button = Ext.create(Ext.apply({
                    renderTo: document.body,
                    xtype: 'button',
                    width: shrinkWidth ? null : 100,
                    height: shrinkHeight ? null : 100
                }, config || {}));
            }

            function getButtonText(width, height) {
                var style = '';

                if (width) {
                    style += 'width:' + width + 'px;';
                }

                if (height) {
                    style += 'height:' + height + 'px;';
                }

                return '<div class="btn-text-content" style="' + style + 'display:inline-block;background:red;">&nbsp;</div>';
            }

            // expects the icon's background-position to be 'center center'.
            // this position should be present for all alignments of the icon element
            function expectIconPosition() {
                var btnIconEl = button.btnIconEl,
                    backgroundPosition;

                if (Ext.isIE9m) {
                    expect(btnIconEl.dom.currentStyle.backgroundPositionX).toBe('center');
                    expect(btnIconEl.dom.currentStyle.backgroundPositionY).toBe('center');
                }
                else {
                    backgroundPosition = btnIconEl.getStyle('background-position');
                    expect(backgroundPosition === '50% 50%' || backgroundPosition === '50%').toBe(true);
                }
            }

            // since the arrow is created using an :after pseudo element its layout
            // cannot be verified using the toHaveLayout matcher.  The closest we can get
            // is to check its computed style to ensure it has the right height, width,
            // and display properties
            function expectArrowStyle(props) {
                if (!window.getComputedStyle) {
                    // IE8 will just have to do without these expectations for now.
                    return;
                }

                var style = window.getComputedStyle(button.btnWrap.dom, ':after'),
                    display = props.display;

                if (Ext.isOpera12m && display === 'table-row') {
                    display = 'table-row-group';
                }

                if (display === 'flex') {
                    expect(style.display === 'flex' || style.display === '-ms-flexbox' || style.display === '-webkit-box').toBe(true);
                }
                else {
                    expect(style.display).toBe(display);
                }

                if (Ext.isWebKit) {
                    // width/height check can only be done in webkit, the other browsers
                    // return 'auto' instead of a px width for the computed style of
                    // auto sized elements
                    expect(style.width).toBe(props.width);
                    expect(style.height).toBe(props.height);
                }
            }

            afterEach(function() {
                Ext.util.CSS.removeStyleSheet('btnSpecStyleSheet');
            });

            describe((shrinkWrap ? ("shrink wrap " + dimensions[shrinkWrap] + (stretch ? ' - stretched height content' : '')) : "fixed width and height"), function() {
                describe("no icon or arrow", function() {
                    function make(config) {
                        Ext.apply(config, {
                            text: getButtonText(
                                shrinkWidth ? 86 : 20,
                                stretch ? 94 : null
                            )
                        });

                        makeButton(config);
                    }

                    it("should layout with textAlign:left", function() {
                        make({
                            textAlign: 'left'
                        });

                        expect(button).toHaveLayout({
                            el: {
                                w: 100,
                                h: (shrinkHeight && !stretch) ? 22 : 100
                            },
                            '.btn-text-content': {
                                x: 7,
                                y: shrinkHeight ? 3 : 42,
                                w: shrinkWidth ? 86 : 20,
                                h: stretch ? 94 : 16
                            }
                        });
                    });

                    it("should layout with textAlign:center", function() {
                        make({
                            textAlign: 'center'
                        });

                        expect(button).toHaveLayout({
                            el: {
                                w: 100,
                                h: (shrinkHeight && !stretch) ? 22 : 100
                            },
                            '.btn-text-content': {
                                x: shrinkWidth ? 7 : 40,
                                y: shrinkHeight ? 3 : 42,
                                w: shrinkWidth ? 86 : 20,
                                h: stretch ? 94 : 16
                            }
                        });
                    });

                    it("should layout with textAlign:right", function() {
                        make({
                            textAlign: 'right'
                        });

                        expect(button).toHaveLayout({
                            el: {
                                w: 100,
                                h: (shrinkHeight && !stretch) ? 22 : 100
                            },
                            '.btn-text-content': {
                                x: shrinkWidth ? 7 : 73,
                                y: shrinkHeight ? 3 : 42,
                                w: shrinkWidth ? 86 : 20,
                                h: stretch ? 94 : 16
                            }
                        });
                    });
                });

                describe("with icon", function() {
                    function make(config) {
                        var iconVertical = (config.iconAlign === 'top' || config.iconAlign === 'bottom');

                        makeButton(Ext.apply({
                            iconCls: 'spec-icon',
                            text: getButtonText(
                                shrinkWidth ? (iconVertical ? 86 : 70) : 20,
                                stretch ? (iconVertical ? 74 : 94) : null
                            )
                        }, config));
                    }

                    describe("iconAlign:top", function() {
                        it("no text", function() {
                            make({
                                text: '',
                                iconAlign: 'top'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: shrinkWidth ? 22 : 100,
                                    h: shrinkHeight ? 22 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 16 : 94,
                                    h: 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:left", function() {
                            make({
                                textAlign: 'left',
                                iconAlign: 'top'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? 3 : 32,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 23 : 52,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:center", function() {
                            make({
                                textAlign: 'center',
                                iconAlign: 'top'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? 3 : 32,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 40,
                                    y: shrinkHeight ? 23 : 52,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:right", function() {
                            make({
                                textAlign: 'right',
                                iconAlign: 'top'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? 3 : 32,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 73,
                                    y: shrinkHeight ? 23 : 52,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });
                    });

                    describe("iconAlign:right", function() {
                        it("no text", function() {
                            make({
                                text: '',
                                iconAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: shrinkWidth ? 22 : 100,
                                    h: shrinkHeight ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 3 : 42,
                                    y: shrinkHeight ? 3 : 42,
                                    w: 16,
                                    h: 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:left", function() {
                            make({
                                textAlign: 'left',
                                iconAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 81 : 31,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:center", function() {
                            make({
                                textAlign: 'center',
                                iconAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 81 : 56,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 32,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:right", function() {
                            make({
                                textAlign: 'right',
                                iconAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: 81,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 57,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });
                    });

                    describe("iconAlign:bottom", function() {
                        it("no text", function() {
                            make({
                                text: '',
                                iconAlign: 'bottom'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: shrinkWidth ? 22 : 100,
                                    h: shrinkHeight ? 22 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 16 : 94,
                                    h: 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:left", function() {
                            make({
                                textAlign: 'left',
                                iconAlign: 'bottom'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 32,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:center", function() {
                            make({
                                textAlign: 'center',
                                iconAlign: 'bottom'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 40,
                                    y: shrinkHeight ? 3 : 32,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:right", function() {
                            make({
                                textAlign: 'right',
                                iconAlign: 'bottom'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 42 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                    w: 94,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 73,
                                    y: shrinkHeight ? 3 : 32,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 74 : 16
                                }
                            });

                            expectIconPosition();
                        });
                    });

                    describe("iconAlign:left", function() {
                        it("no text", function() {
                            make({
                                text: '',
                                iconAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: shrinkWidth ? 22 : 100,
                                    h: shrinkHeight ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 3 : 42,
                                    y: shrinkHeight ? 3 : 42,
                                    w: 16,
                                    h: 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:left", function() {
                            make({
                                textAlign: 'left',
                                iconAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: 3,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: 23,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:center", function() {
                            make({
                                textAlign: 'center',
                                iconAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 3 : 28,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 23 : 48,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });

                        it("textAlign:right", function() {
                            make({
                                textAlign: 'right',
                                iconAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                btnIconEl: {
                                    x: shrinkWidth ? 3 : 53,
                                    y: (shrinkHeight && !stretch) ? 3 : 42,
                                    w: 16,
                                    h: 16
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 23 : 73,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 70 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectIconPosition();
                        });
                    });
                });

                describe("with arrow", function() {
                    function make(config) {
                        makeButton(Ext.apply({
                            menu: [],
                            text: getButtonText(
                                shrinkWidth ? (config.arrowAlign === 'bottom' ? 86 : 78) : 20,
                                stretch ? (config.arrowAlign === 'bottom' ? 84 : 94) : null
                            )
                        }, config));
                    }

                    describe("arrowAlign:right", function() {
                        it("textAlign:left", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 78 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '8px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });

                        it("textAlign:center", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'center'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 36,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 78 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '8px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });

                        it("textAlign:right", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 65,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 78 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '8px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });
                    });

                    describe("arrowAlign:bottom", function() {
                        it("textAlign:left", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 32 : 100
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 37,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 84 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '8px'
                            });
                        });

                        it("textAlign:center", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'center'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 32 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 40,
                                    y: shrinkHeight ? 3 : 37,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 84 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '8px'
                            });
                        });

                        it("textAlign:right", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 32 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 73,
                                    y: shrinkHeight ? 3 : 37,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 84 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '8px'
                            });
                        });
                    });
                });

                describe("with split arrow", function() {
                    function make(config) {
                        makeButton(Ext.apply({
                            xtype: 'splitbutton',
                            text: getButtonText(
                                shrinkWidth ? (config.arrowAlign === 'bottom' ? 86 : 72) : 20,
                                stretch ? (config.arrowAlign === 'bottom' ? 78 : 94) : null
                            )
                        }, config));
                    }

                    describe("arrowAlign:right", function() {
                        it("textAlign:left", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 72 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '14px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });

                        it("textAlign:center", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'center'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 33,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 72 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '14px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });

                        it("textAlign:right", function() {
                            make({
                                arrowAlign: 'right',
                                textAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 22 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 59,
                                    y: shrinkHeight ? 3 : 42,
                                    w: shrinkWidth ? 72 : 20,
                                    h: stretch ? 94 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-cell' : 'flex',
                                width: '14px',
                                height: (shrinkHeight && !stretch) ? '16px' : '94px'
                            });
                        });
                    });

                    describe("arrowAlign:bottom", function() {
                        it("textAlign:left", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'left'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 38 : 100
                                },
                                '.btn-text-content': {
                                    x: 7,
                                    y: shrinkHeight ? 3 : 34,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 78 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '14px'
                            });
                        });

                        it("textAlign:center", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'center'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 38 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 40,
                                    y: shrinkHeight ? 3 : 34,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 78 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '14px'
                            });
                        });

                        it("textAlign:right", function() {
                            make({
                                arrowAlign: 'bottom',
                                textAlign: 'right'
                            });

                            expect(button).toHaveLayout({
                                el: {
                                    w: 100,
                                    h: (shrinkHeight && !stretch) ? 38 : 100
                                },
                                '.btn-text-content': {
                                    x: shrinkWidth ? 7 : 73,
                                    y: shrinkHeight ? 3 : 34,
                                    w: shrinkWidth ? 86 : 20,
                                    h: stretch ? 78 : 16
                                }
                            });

                            expectArrowStyle({
                                display: Ext.isIE9m ? 'table-row' : 'block',
                                width: '94px',
                                height: '14px'
                            });
                        });
                    });
                });

                describe("with icon and arrow", function() {
                    function make(config) {
                        var iconAlign = config.iconAlign,
                            bottomArrow = config.arrowAlign === 'bottom',
                            textWidth,
                            textHeight;

                        if (iconAlign === 'top' || iconAlign === 'bottom') {
                            textWidth = bottomArrow ? 86 : 78;
                            textHeight = bottomArrow ? 64 : 74;
                        }
                        else if (iconAlign === 'right') {
                            textWidth = bottomArrow ? 70 : 58;
                            textHeight = bottomArrow ? 84 : 94;
                        }
                        else if (iconAlign === 'left') {
                            textWidth = bottomArrow ? 70 : 62;
                            textHeight = bottomArrow ? 84 : 94;
                        }

                        makeButton(Ext.apply({
                            iconCls: 'spec-icon',
                            menu: [],
                            text: getButtonText(
                                shrinkWidth ? textWidth : 20,
                                stretch ? textHeight : null
                            )
                        }, config));
                    }

                    describe("iconAlign:top", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 34 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 16 : 82,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 36,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 65,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 16 : 94,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 27,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 23 : 47,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 27,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 40,
                                        y: shrinkHeight ? 23 : 47,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 27,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 73,
                                        y: shrinkHeight ? 23 : 47,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:right", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 34 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 36,
                                        y: shrinkHeight ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 69 : 31,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 58 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 69 : 50,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 26,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 58 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 69,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 45,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 58 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 42,
                                        y: shrinkHeight ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 81 : 31,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 81 : 56,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 32,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: 81,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 57,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:bottom", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 34 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 16 : 82,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 36,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 86,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 65,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 78 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 16 : 94,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 71 : 23) : 47,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: (shrinkHeight || stretch) ? 3 : 27,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 71 : 23) : 47,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 40,
                                        y: (shrinkHeight || stretch) ? 3 : 27,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 52 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 71 : 23) : 47,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 73,
                                        y: (shrinkHeight || stretch) ? 3 : 27,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 64 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:left", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 34 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 36,
                                        y: shrinkHeight ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 23,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 62 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 24,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 44,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 62 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 45,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 65,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 62 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '8px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 42,
                                        y: shrinkHeight ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 23,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 28,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 48,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 32 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 53,
                                        y: (shrinkHeight && !stretch) ? 3 : 37,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 73,
                                        y: shrinkHeight ? 3 : 37,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 84 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '8px'
                                });
                            });
                        });
                    });
                });

                describe("with icon and split arrow", function() {
                    function make(config) {
                        var iconAlign = config.iconAlign,
                            bottomArrow = config.arrowAlign === 'bottom',
                            textWidth,
                            textHeight;

                        if (iconAlign === 'top' || iconAlign === 'bottom') {
                            textWidth = bottomArrow ? 86 : 72;
                            textHeight = bottomArrow ? 58 : 74;
                        }
                        else if (iconAlign === 'right') {
                            textWidth = bottomArrow ? 70 : 52;
                            textHeight = bottomArrow ? 78 : 94;
                        }
                        else if (iconAlign === 'left') {
                            textWidth = bottomArrow ? 70 : 56;
                            textHeight = bottomArrow ? 78 : 94;
                        }

                        makeButton(Ext.apply({
                            xtype: 'splitbutton',
                            iconCls: 'spec-icon',
                            text: getButtonText(
                                shrinkWidth ? textWidth : 20,
                                stretch ? textHeight : null
                            )
                        }, config));
                    }

                    describe("iconAlign:top", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 40 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 16 : 76,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 33,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 32,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 59,
                                        y: shrinkHeight ? 23 : 52,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 16 : 94,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 24,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 23 : 44,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 24,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 40,
                                        y: shrinkHeight ? 23 : 44,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'top',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 24,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 73,
                                        y: shrinkHeight ? 23 : 44,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:right", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 40 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 33,
                                        y: shrinkHeight ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 63 : 31,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 52 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 63 : 47,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 23,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 52 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 63,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 39,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 52 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 42,
                                        y: shrinkHeight ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 81 : 31,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 81 : 56,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 32,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'right',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: 81,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 57,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:bottom", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 40 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 16 : 76,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 33,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 42 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 81 : 23) : 52,
                                        w: 80,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 59,
                                        y: (shrinkHeight || stretch) ? 3 : 32,
                                        w: shrinkWidth ? 72 : 20,
                                        h: stretch ? 74 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '36px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 16 : 94,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 65 : 23) : 44,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 7,
                                        y: (shrinkHeight || stretch) ? 3 : 24,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 65 : 23) : 44,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 40,
                                        y: (shrinkHeight || stretch) ? 3 : 24,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'bottom',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 58 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: shrinkHeight ? (stretch ? 65 : 23) : 44,
                                        w: 94,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 7 : 73,
                                        y: (shrinkHeight || stretch) ? 3 : 24,
                                        w: shrinkWidth ? 86 : 20,
                                        h: stretch ? 58 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });
                        });
                    });

                    describe("iconAlign:left", function() {
                        describe("arrowAlign:right", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 40 : 100,
                                        h: shrinkHeight ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 33,
                                        y: shrinkHeight ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: shrinkHeight ? '16px' : '94px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 23,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 56 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 21,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 41,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 56 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'right',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 22 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 39,
                                        y: (shrinkHeight && !stretch) ? 3 : 42,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 59,
                                        y: shrinkHeight ? 3 : 42,
                                        w: shrinkWidth ? 56 : 20,
                                        h: stretch ? 94 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-cell' : 'flex',
                                    width: '14px',
                                    height: (shrinkHeight && !stretch) ? '16px' : '94px'
                                });
                            });
                        });

                        describe("arrowAlign:bottom", function() {
                            it("no text", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    text: ''
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: shrinkWidth ? 22 : 100,
                                        h: shrinkHeight ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 42,
                                        y: shrinkHeight ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: shrinkWidth ? '16px' : '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:left", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'left'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: 3,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: 23,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:center", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'center'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 28,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 48,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });

                            it("textAlign:right", function() {
                                make({
                                    iconAlign: 'left',
                                    arrowAlign: 'bottom',
                                    textAlign: 'right'
                                });

                                expect(button).toHaveLayout({
                                    el: {
                                        w: 100,
                                        h: (shrinkHeight && !stretch) ? 38 : 100
                                    },
                                    btnIconEl: {
                                        x: shrinkWidth ? 3 : 53,
                                        y: (shrinkHeight && !stretch) ? 3 : 34,
                                        w: 16,
                                        h: 16
                                    },
                                    '.btn-text-content': {
                                        x: shrinkWidth ? 23 : 73,
                                        y: shrinkHeight ? 3 : 34,
                                        w: shrinkWidth ? 70 : 20,
                                        h: stretch ? 78 : 16
                                    }
                                });

                                expectIconPosition();

                                expectArrowStyle({
                                    display: Ext.isIE9m ? 'table-row' : 'block',
                                    width: '94px',
                                    height: '14px'
                                });
                            });
                        });
                    });
                });
            });
        }

        makeLayoutSuite(0); // fixed width and height
        makeLayoutSuite(1); // shrinkWrap width
        makeLayoutSuite(2); // shrinkWrap height
        makeLayoutSuite(2, true); // shrinkWrap height, stretch contents vertically
        makeLayoutSuite(3); // shrinkWrap both
        makeLayoutSuite(3, true); // shrinkWrap both, stretch contents vertically

        describe("syncing the table-layout of the btnWrap when the button width changes", function() {
            var btnWrap;

            describe("setting the width", function() {
                beforeEach(function() {
                    button = Ext.create({
                        xtype: 'button',
                        renderTo: document.body,
                        text: 'Hello'
                    });
                    btnWrap = button.btnWrap;
                });

                it("should initially render with table-layout:auto", function() {
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should add table-layout:fixed - using component.setWidth()", function() {
                    button.setWidth(100);
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should add table-layout:fixed - using component.setSize()", function() {
                    button.setSize(100, 100);
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should add table-layout:fixed - using el.setWidth()", function() {
                    button.el.setWidth(100);
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should add table-layout:fixed - using el.setSize()", function() {
                    button.el.setSize(100, 100);
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should add table-layout:fixed - using el.setStyle('width')", function() {
                    button.el.setStyle('width', '100px');
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should add table-layout:fixed - using el.setStyle({ width: width }}", function() {
                    button.el.setStyle({ width: '100px' });
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });
            });

            describe("removing the width", function() {
                beforeEach(function() {
                    button = Ext.create({
                        xtype: 'button',
                        renderTo: document.body,
                        width: 100,
                        text: 'Hello'
                    });
                    btnWrap = button.btnWrap;
                });

                it("should initially render with table-layout:fixed", function() {
                    expect(btnWrap.getStyle('table-layout')).toBe('fixed');
                });

                it("should remove table-layout:fixed - using component.setWidth()", function() {
                    button.setWidth(null);
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should remove table-layout:fixed - using component.setSize()", function() {
                    button.setSize(null, null);
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should remove table-layout:fixed - using el.setWidth()", function() {
                    button.el.setWidth(null);
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should remove table-layout:fixed - using el.setSize()", function() {
                    button.el.setSize(null, null);
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should remove table-layout:fixed - using el.setStyle('width')", function() {
                    button.el.setStyle('width', '');
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });

                it("should remove table-layout:fixed - using el.setStyle({ width: width }}", function() {
                    button.el.setStyle({ width: '' });
                    expect(btnWrap.getStyle('table-layout')).toBe('auto');
                });
            });
        });

        describe("syncing the height style of the btnEl when the button height changes", function() {
            var btnEl;

            describe("setting the height", function() {
                beforeEach(function() {
                    button = Ext.create({
                        xtype: 'button',
                        renderTo: document.body,
                        text: 'Hello'
                    });
                    btnEl = button.btnEl;
                });

                it("should initially render with a fixed height from the stylesheet", function() {
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should add height:auto - using component.setHeight()", function() {
                    button.setHeight(100);
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should add height:auto - using component.setSize()", function() {
                    button.setSize(100, 100);
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should add height:auto - using el.setHeight()", function() {
                    button.el.setHeight(100);
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should add height:auto - using el.setSize()", function() {
                    button.el.setSize(100, 100);
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should add height:auto - using el.setStyle('height')", function() {
                    button.el.setStyle('height', '100px');
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should add height:auto - using el.setStyle({ height: height }}", function() {
                    button.el.setStyle({ height: '100px' });
                    expect(btnEl.dom.style.height).toBe('auto');
                });
            });

            describe("removing the height", function() {
                beforeEach(function() {
                    button = Ext.create({
                        xtype: 'button',
                        renderTo: document.body,
                        height: 100,
                        text: 'Hello'
                    });
                    btnEl = button.btnEl;
                });

                it("should initially render with auto height", function() {
                    expect(btnEl.dom.style.height).toBe('auto');
                });

                it("should remove height:auto - using component.setHeight()", function() {
                    button.setHeight(null);
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should remove height:auto - using component.setSize()", function() {
                    button.setSize(null, null);
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should remove height:auto - using el.setHeight()", function() {
                    button.el.setHeight(null);
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should remove height:auto - using el.setSize()", function() {
                    button.el.setSize(null, null);
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should remove height:auto - using el.setStyle('height')", function() {
                    button.el.setStyle('height', '');
                    expect(btnEl.dom.style.height).toBe('');
                });

                it("should remove height:auto - using el.setStyle({ height: height }}", function() {
                    button.el.setStyle({ height: '' });
                    expect(btnEl.dom.style.height).toBe('');
                });
            });
        });

        if (Ext.isIE8) {
            describe("syncing the frame height when the button height changes", function() {
                var frameBody;

                describe("setting the height", function() {
                    beforeEach(function() {
                        button = Ext.create({
                            xtype: 'button',
                            renderTo: document.body,
                            text: 'Hello'
                        });
                        frameBody = button.frameBody;
                    });

                    it("should initially render with auto height", function() {
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should set the frameBody height - using component.setHeight()", function() {
                        button.setHeight(100);
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should set the frameBody height - using component.setSize()", function() {
                        button.setSize(100, 100);
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should set the frameBody height - using el.setHeight()", function() {
                        button.el.setHeight(100);
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should set the frameBody height - using el.setSize()", function() {
                        button.el.setSize(100, 100);
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should set the frameBody height - using el.setStyle('height')", function() {
                        button.el.setStyle('height', '100px');
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should set the frameBody height - using el.setStyle({ height: height }}", function() {
                        button.el.setStyle({ height: '100px' });
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });
                });

                describe("removing the height", function() {
                    beforeEach(function() {
                        button = Ext.create({
                            xtype: 'button',
                            renderTo: document.body,
                            height: 100,
                            text: 'Hello'
                        });
                        frameBody = button.frameBody;
                    });

                    it("should initially render with the specified height", function() {
                        expect(frameBody.getStyle('height')).toBe('94px');
                    });

                    it("should remove the frameBody height - using component.setHeight()", function() {
                        button.setHeight(null);
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should remove the frameBody height - using component.setSize()", function() {
                        button.setSize(null, null);
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should remove the frameBody height - using el.setHeight()", function() {
                        button.el.setHeight(null);
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should remove the frameBody height - using el.setSize()", function() {
                        button.el.setSize(null, null);
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should remove the frameBody height - using el.setStyle('height')", function() {
                        button.el.setStyle('height', '');
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });

                    it("should remove the frameBody height - using el.setStyle({ height: height }}", function() {
                        button.el.setStyle({ height: '' });
                        expect(frameBody.getStyle('height')).toBe('auto');
                    });
                });
            });
        }

        it("should be able to have a height of 0", function() {
            expect(function() {
                makeButton({
                    renderTo: Ext.getBody(),
                    height: 0
                });
            }).not.toThrow();
        });

        it("should be able to size larger after hitting a minWidth constraint", function() {
            makeButton({
                renderTo: Ext.getBody(),
                minWidth: 75,
                text: 'Foo'
            });
            button.setText('Text that will stretch longer than 75px');
            expect(button.getWidth()).toBeGreaterThan(75);
        });

        it("should layout shrinkwrap width button with right arrow in an overflowing hbox layout", function() {
            // ARIA warnings about splitbuttons are expected
            spyOn(Ext.log, 'warn');

            var toolbar = Ext.create({
                xtype: 'toolbar',
                renderTo: document.body,
                width: 75,
                overflowHandler: 'scroller',
                items: [{
                    xtype: 'splitbutton',
                    text: '<span style="display:inline-block;width:72px;background-color:red;"></span>'
                }, {
                    xtype: 'button',
                    text: '<span style="display:inline-block;width:78px;background-color:red;"></span>',
                    menu: []
                }]
            });

            expect(toolbar.items.getAt(0).getWidth()).toBe(100);
            expect(toolbar.items.getAt(1).getWidth()).toBe(100);

            toolbar.destroy();
        });

        it("should layout shrinkwrap height button with bottom arrow in an overflowing vbox layout", function() {
            // ARIA warnings about splitbuttons are expected
            spyOn(Ext.log, 'warn');

            var toolbar = Ext.create({
                xtype: 'toolbar',
                renderTo: document.body,
                height: 75,
                vertical: true,
                overflowHandler: 'scroller',
                items: [{
                    xtype: 'splitbutton',
                    arrowAlign: 'bottom',
                    text: '<div style="display:inline-block;width:86px;height:78px;background-color:red;">&nbsp;</div>'
                }, {
                    xtype: 'button',
                    arrowAlign: 'bottom',
                    menu: [],
                    text: '<div style="display:inline-block;width:86px;height:84px;background-color:red;">&nbsp;</div>'
                }]
            });

            expect(toolbar.items.getAt(0).getWidth()).toBe(100);
            expect(toolbar.items.getAt(1).getWidth()).toBe(100);

            toolbar.destroy();
        });

        (Ext.isIE8 ? xit : it)("should layout with overflowing text", function() {
            button = Ext.create({
                xtype: 'button',
                renderTo: document.body,
                text: '<div style="display:inline-block;width:142px;background:red;">&nbsp;</div>',
                menu: [],
                width: 50
            });

            expect(button).toHaveLayout({
                el: {
                    w: 50,
                    h: 22
                },
                btnWrap: {
                    x: 3,
                    y: 3,
                    w: 44,
                    h: 16
                },
                btnInnerEl: {
                    x: 3,
                    y: 3,
                    w: 36,
                    h: 16
                }
            });
        });
    }); // layout

    describe("binding", function() {
        it("should publish \"pressed\" state by default", function() {
            makeButton({
                viewModel: {
                    data: {
                        foo: false
                    }
                },
                enableToggle: true,
                bind: {
                    pressed: '{foo}'
                }
            });

            var vm = button.getViewModel();

            button.setPressed(true);
            vm.notify();
            expect(vm.get('foo')).toBe(true);
            button.setPressed(false);
            vm.notify();
            expect(vm.get('foo')).toBe(false);
        });

        it("should publish \"pressed\" state with reference", function() {
            makeButton({
                viewModel: true,
                enableToggle: true,
                reference: 'btn'
            });

            var vm = button.getViewModel();

            button.setPressed(true);
            vm.notify();
            expect(vm.get('btn.pressed')).toBe(true);
            button.setPressed(false);
            vm.notify();
            expect(vm.get('btn.pressed')).toBe(false);
        });

        describe("menu", function() {
            var vm;

            beforeEach(function() {
                vm = new Ext.app.ViewModel({
                    data: {
                        title: 'someTitle',
                        text: 'otherText'
                    }
                });
            });

            afterEach(function() {
                vm = null;
            });

            it("should be able to bind properties higher up in the hierarchy", function() {
                makeButton({
                    renderTo: Ext.getBody(),
                    text: 'Foo',
                    viewModel: vm,
                    menu: {
                        bind: {
                            title: '{title}'
                        },
                        items: {
                            bind: {
                                text: '{text}'
                            }
                        }
                    }
                });
                var menu = button.getMenu();

                // Render it to trigger the bindings to initialize
                menu.show();
                vm.notify();
                expect(menu.getTitle()).toBe('someTitle');
                expect(menu.items.first().text).toBe('otherText');
            });

            it("should be able to bind when dynamically setting a menu", function() {
                makeButton({
                    renderTo: Ext.getBody(),
                    text: 'Foo',
                    viewModel: vm
                });
                button.setMenu([{
                    bind: {
                        text: '{text}'
                    }
                }]);
                var menu = button.getMenu();

                menu.show();
                vm.notify();
                expect(menu.items.first().text).toBe('otherText');
            });
        });
    });

    describe("default ARIA attributes", function() {
        beforeEach(function() {
            makeButton({
                renderTo: Ext.getBody()
            });
        });

        it("should not render aria-haspopup", function() {
            expect(button).not.toHaveAttr('aria-haspopup');
        });

        it("should not render aria-pressed", function() {
            expect(button).not.toHaveAttr('aria-pressed');
        });
    });

    describe("tabIndex", function() {
        describe("rendering", function() {
            it("should render tabIndex when not disabled", function() {
                createButton();

                expect(button).toHaveAttr('tabIndex', '0');
            });

            it("should not render tabIndex when disabled", function() {
                createButton({ disabled: true });

                expect(button).not.toHaveAttr('tabIndex');
            });
        });

        describe("disabling", function() {
            beforeEach(function() {
                createButton();
                button.disable();
            });

            it("should remove tabIndex when disabled", function() {
                expect(button).not.toHaveAttr('tabIndex');
            });

            it("should add tabIndex back when re-enabled", function() {
                button.enable();
                expect(button).toHaveAttr('tabIndex', '0');
            });
        });
    });

    describe("click", function() {
        beforeEach(function() {
            makeButton({
                renderTo: Ext.getBody()
            });
        });

        it("should allow event argument to be optional", function() {
            expect(function() {
                button.click();
            }).not.toThrow();
        });
    });

    describe("toggle", function() {
        beforeEach(function() {
            makeButton({
                renderTo: Ext.getBody(),
                enableToggle: true
            });
        });

        describe("aria-pressed", function() {
            describe("setup", function() {
                it("should render", function() {
                    expect(button.ariaEl.dom.hasAttribute('aria-pressed')).toBe(true);
                });

                it("should equal pressed state", function() {
                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });

            describe("programmatic toggling", function() {
                it("should be set to true when toggled", function() {
                    button.toggle();

                    expect(button).toHaveAttr('aria-pressed', 'true');
                });

                it("should be set to false when toggled back", function() {
                    button.toggle();
                    button.toggle();

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });

            describe("clicking", function() {
                it("should be set to true when clicked", function() {
                    clickIt();

                    expect(button).toHaveAttr('aria-pressed', 'true');
                });

                it("should be set to false when clicked twice", function() {
                    clickIt();
                    clickIt();

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });

            describe("clicking with veto", function() {
                beforeEach(function() {
                    button.addListener({
                        beforetoggle: function() {
                            return false;
                        }
                    });
                });
                it("should not be set to true when clicked", function() {
                    clickIt();

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });

            describe("keyboarding", function() {
                it("should be set to true when Space key is pressed", function() {
                    jasmine.simulateKey(button, 'space');

                    expect(button).toHaveAttr('aria-pressed', 'true');
                });

                it("should be set to false when Space key is pressed twice", function() {
                    jasmine.simulateKey(button, 'space');
                    jasmine.simulateKey(button, 'space');

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });

                it("should be set to true when Enter key is pressed", function() {
                    jasmine.simulateKey(button, 'enter');

                    expect(button).toHaveAttr('aria-pressed', 'true');
                });

                it("should be set to false when Enter key is pressed twice", function() {
                    jasmine.simulateKey(button, 'enter');
                    jasmine.simulateKey(button, 'enter');

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });

            describe("keyboarding with veto", function() {
                beforeEach(function() {
                    button.addListener({
                        beforetoggle: function() {
                            return false;
                        }
                    });
                });
                it("should not be set to true when Space key is pressed", function() {
                    jasmine.simulateKey(button, 'space');

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });

                it("should not be set to true when Enter key is pressed", function() {
                    jasmine.simulateKey(button, 'enter');

                    expect(button).toHaveAttr('aria-pressed', 'false');
                });
            });
        });
    });

    describe("disable/enable", function() {
        describe("from parent", function() {
            it("should remain disabled if configured as disabled and the parent is enabled", function() {
                makeButton({
                    disabled: true
                });

                var ct = new Ext.container.Container({
                    renderTo: document.body,
                    items: button
                });

                ct.disable();
                expect(button.el).toHaveCls(button._disabledCls);
                expect(button.disabled).toBe(true);
                ct.enable();
                expect(button.el).toHaveCls(button._disabledCls);
                expect(button.disabled).toBe(true);

                ct.destroy();
            });
        });
    });

    describe("keyboard interaction", function() {
        var handlerSpy, enterSpy, downSpy;

        beforeEach(function() {
            handlerSpy = jasmine.createSpy('button handler');

            makeButton({
                text: 'foo',
                handler: handlerSpy
            });

            enterSpy = spyOn(button, 'onEnterKey').andCallThrough();
            downSpy  = spyOn(button, 'onDownKey').andCallThrough();

            button.render(Ext.getBody());
        });

        afterEach(function() {
            handlerSpy = enterSpy = downSpy = null;
        });

        describe("Space key", function() {
            beforeEach(function() {
                jasmine.pressKey(button.el, 'space');

                waitForSpy(enterSpy);
            });

            it("should have fired the handler", function() {
                expect(handlerSpy).toHaveBeenCalled();
            });

            it("should stop the keydown event", function() {
                var args = enterSpy.mostRecentCall.args;

                expect(args[0].stopped).toBe(true);
            });

            it("should return false to stop Event propagation loop", function() {
                expect(enterSpy.mostRecentCall.result).toBe(false);
            });
        });

        describe("Enter key", function() {
            beforeEach(function() {
                jasmine.pressKey(button.el, 'enter');

                waitForSpy(enterSpy);
            });

            it("should have fired the handler", function() {
                expect(handlerSpy).toHaveBeenCalled();
            });

            it("should stop the keydown event", function() {
                var args = enterSpy.mostRecentCall.args;

                expect(args[0].stopped).toBe(true);
            });

            it("should return false to stop Event propagation loop", function() {
                expect(enterSpy.mostRecentCall.result).toBe(false);
            });
        });

        describe("Down key", function() {
            beforeEach(function() {
                jasmine.pressKey(button.el, 'down');

                waitForSpy(downSpy);
            });

            it("should NOT have fired the handler", function() {
                expect(handlerSpy).not.toHaveBeenCalled();
            });

            it("should NOT stop the keydown event", function() {
                var args = downSpy.mostRecentCall.args;

                expect(args[0].stopped).toBeFalsy();
            });

            it("should NOT return false to stop Event propagation loop", function() {
                expect(downSpy.mostRecentCall.result).not.toBeDefined();
            });
        });
    });
});
