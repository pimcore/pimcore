topSuite("Ext.Img", function() {
    var senchaPng = '../../../../test/local/sencha.png',
        img, defaultFamily;

    function makeBaseImage(cfg) {
        img = new Ext.Img(Ext.apply({
        }, cfg));
    }

    function makeImage(cfg) {
        img = new Ext.Img(Ext.apply({
            renderTo: Ext.getBody(),
            alt: 'Image'
        }, cfg));
    }

    beforeEach(function() {
        defaultFamily = Ext._glyphFontFamily;
        Ext._glyphFontFamily = 'FooFont';
    });

    afterEach(function() {
        Ext._glyphFontFamily = defaultFamily;
        Ext.destroy(img);
        defaultFamily = img = null;
    });

    describe("glyph", function() {

        function expectGlyph(code) {
            expect(img.el.dom.innerHTML.charCodeAt(0)).toBe(code);
        }

        function expectFontFamily(family) {
            expect(img.el.getStyle('font-family')).toBe(family);
        }

        describe("initial configuration", function() {
            it("should set a numeric glyph & use the default font family", function() {
                makeImage({
                    glyph: 1234
                });
                expectGlyph(1234);
                expectFontFamily('FooFont');
            });

            it("should accept a string glyph & use the default font family", function() {
                makeImage({
                    glyph: '2345'
                });
                expectGlyph(2345);
                expectFontFamily('FooFont');
            });

            it("should accept a string glyph with the font family", function() {
                makeImage({
                    glyph: '3456@BarFont'
                });
                expectGlyph(3456);
                expectFontFamily('BarFont');
            });

            it("should not override other font styles", function() {
                makeImage({
                    glyph: '1234@BarFont',
                    style: 'font-size: 40px;'
                });
                expectGlyph(1234);
                expectFontFamily('BarFont');
                expect(img.el.getStyle('font-size')).toBe('40px');
            });

            it("should have img role", function() {
                makeImage({
                    glyph: '1234'
                });

                expect(img).toHaveAttr('role', 'img');
            });
        });

        describe("setGlyph", function() {
            describe("before render", function() {
                it("should be able to overwrite a glyph", function() {
                    makeImage({
                        renderTo: null,
                        glyph: '4321'
                    });
                    img.setGlyph(1234);
                    img.render(Ext.getBody());
                    expectGlyph(1234);
                    expectFontFamily('FooFont');
                });

                it("should be able to overwrite a glyph with a font family", function() {
                    makeImage({
                        renderTo: null,
                        glyph: '4321@BarFont'
                    });
                    img.setGlyph('1234@BazFont');
                    img.render(Ext.getBody());
                    expectGlyph(1234);
                    expectFontFamily('BazFont');
                });

                it("should not overwrite other font styles", function() {
                    makeImage({
                        renderTo: null,
                        glyph: '4321',
                        style: 'font-size: 32px;'
                    });
                    img.setGlyph('1234@BarFont');
                    img.render(Ext.getBody());
                    expectGlyph(1234);
                    expectFontFamily('BarFont');
                    expect(img.el.getStyle('font-size')).toBe('32px');
                });
            });

            describe("after render", function() {
                it("should be able to overwrite a glyph", function() {
                    makeImage({
                        glyph: '4321'
                    });
                    img.setGlyph(1234);
                    expectGlyph(1234);
                    expectFontFamily('FooFont');
                });

                it("should be able to overwrite a glyph with a font family", function() {
                    makeImage({
                        glyph: '4321@BarFont'
                    });
                    img.setGlyph('1234@BazFont');
                    expectGlyph(1234);
                    expectFontFamily('BazFont');
                });

                it("should use the default font if initially configured with a font and a new one is not provided", function() {
                    makeImage({
                        glyph: '4321@BarFont'
                    });
                    img.setGlyph('1234');
                    expectGlyph(1234);
                    expectFontFamily('FooFont');
                });

                it("should not overwrite other font styles", function() {
                    makeImage({
                        glyph: '4321',
                        style: 'font-size: 32px;'
                    });
                    img.setGlyph('1234@BarFont');
                    expectGlyph(1234);
                    expectFontFamily('BarFont');
                    expect(img.el.getStyle('font-size')).toBe('32px');
                });
            });
        });
    });

    describe("img tag", function() {
        beforeEach(function() {
            // Warning here is expected
            spyOn(Ext.log, 'warn');

            img = new Ext.Img({ renderTo: Ext.getBody() });
        });

        it("should have default alt attribute", function() {
            expect(img.el.dom.hasAttribute('alt')).toBe(true);
        });

        it("should not have role attribute", function() {
            expect(img.el.dom.hasAttribute('role')).toBe(false);
        });
    });

    describe("getters and setters", function() {
        describe("before render", function() {
            describe("alt attribute", function() {
                it("should not have default alt value", function() {
                    makeBaseImage();
                    expect(img.getAlt()).toBe('');
                });

                it("should be able to set alt value", function() {
                    makeBaseImage();
                    img.setAlt('Test Alt');
                    expect(img.getAlt()).toBe('Test Alt');
                    img.render(document.body);
                    expect(img.el.dom.alt).toBe('Test Alt');
                });
            });

            describe("title attribute", function() {
                it("should not have default title value", function() {
                     makeBaseImage();
                    expect(img.getTitle()).toBe('');
                });

                it("should be able to set title value", function() {
                    makeBaseImage();
                    img.setTitle('Test Title');
                    expect(img.getTitle()).toBe('Test Title');

                    // Warning here is expected
                    spyOn(Ext.log, 'warn');

                    img.render(document.body);
                    expect(img.el.dom.title).toBe('Test Title');
                });
            });

            describe("src attribute", function() {
                it("should be able to set src value", function() {
                    makeBaseImage();
                    img.setSrc(senchaPng);
                    expect(img.getSrc()).toBe(senchaPng);

                    // Warning here is expected
                    spyOn(Ext.log, 'warn');

                    img.render(document.body);
                    expect(img.el.dom.src.indexOf('sencha.png')).not.toBe(-1);
                });
            });

            describe("configured values", function() {
                it("should be created with configured values", function() {
                    makeBaseImage({
                        title: 'Testing Initial',
                        alt: 'Testing Alt'
                    });

                    expect(img.getTitle()).toBe('Testing Initial');
                    expect(img.getAlt()).toBe('Testing Alt');
                });

                it("should be able to clear configured values", function() {
                    makeBaseImage({
                        title: 'Testing Initial',
                        alt: 'Testing Alt'
                    });

                    img.setTitle();
                    img.setAlt();
                    expect(img.getTitle()).toBe('');
                    expect(img.getAlt()).toBe('');

                    // Warning here is expected
                    spyOn(Ext.log, 'warn');

                    img.render(document.body);
                    expect(img.el.dom.alt).toBe('');
                    expect(img.el.dom.title).toBe('');
                });
            });
        });

        describe("after render", function() {
            describe("alt attribute", function() {
                it("should not have default alt value", function() {
                    // Warning here is expected
                    spyOn(Ext.log, 'warn');

                    img = new Ext.Img({
                        renderTo: document.body
                    });
                    expect(img.el.dom.alt).toBe('');
                });

                it("should be able to set alt value", function() {
                    // Warning here is expected
                    spyOn(Ext.log, 'warn');

                    img = new Ext.Img({
                        renderTo: document.body
                    });
                    img.setAlt('Test Alt');
                    expect(img.getAlt()).toBe('Test Alt');
                    expect(img.el.dom.alt).toBe('Test Alt');
                });

            });

            describe("title attribute", function() {
                it("should not have default title attribute", function() {
                    makeImage();
                    expect(img.el.dom.hasAttribute('title')).toBe(false);
                });

                it("should be able to set title value", function() {
                    makeImage();
                    img.setTitle('Test Title');
                    expect(img.getTitle()).toBe('Test Title');
                    expect(img.el.dom.title).toBe('Test Title');
                });
            });

            describe("src attribute", function() {
                it("should be able to set src value", function() {
                    makeImage();
                    img.setSrc(senchaPng);
                    expect(img.getSrc()).toBe(senchaPng);
                    expect(img.el.dom.src.indexOf('sencha.png')).not.toBe(-1);
                });
            });

            describe("configured values", function() {
                it("should be created with configured values", function() {
                    makeImage({
                        title: 'Testing Initial',
                        alt: 'Testing Alt'
                    });

                    expect(img.el.dom.title).toBe('Testing Initial');
                    expect(img.el.dom.alt).toBe('Testing Alt');
                    img.destroy();
                });

                it("should be able to clear configured values", function() {
                    makeImage({
                        title: 'Testing Initial',
                        alt: 'Testing Alt'
                    });

                    img.setTitle();
                    img.setAlt();
                    expect(img.getTitle()).toBe('');
                    expect(img.getAlt()).toBe('');
                    expect(img.el.dom.alt).toBe('');
                    expect(img.el.dom.title).toBe('');
                    img.destroy();
                });
            });
        });
    });
});
