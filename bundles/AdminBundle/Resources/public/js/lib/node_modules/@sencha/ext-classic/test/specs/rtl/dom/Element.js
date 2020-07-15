topSuite("Ext.rtl.dom.Element", function() {
    var wrap, el;

    beforeEach(function() {
        wrap = Ext.getBody().createChild({
            className: Ext.baseCSSPrefix + 'rtl',
            cn: [{
                style: {
                    width: '100px',
                    height: '100px',
                    right: '15px',
                    top: '20px',
                    position: 'absolute'
                },
                cn: [{
                    style: {
                        width: '40px',
                        height: '40px',
                        right: '6px',
                        top: '7px',
                        'z-index': 10,
                        position: 'absolute'
                    }
                }]
            }]
        });

        el = Ext.fly(wrap.first(null, true)).first();
    });

    afterEach(function() {
        el.destroy();
        wrap.destroy();
    });

    describe("rtlGetLocalX", function() {
        it("should return the local x position", function() {
            expect(el.rtlGetLocalX()).toBe(6);
        });
    });

    describe("rtlGetLocalXY", function() {
        it("should return the local xy position", function() {
            expect(el.rtlGetLocalXY()).toEqual([6, 7]);
        });
    });

    describe("rtlSetLocalX", function() {
        it("should set the local x coordinate to a pixel value", function() {
            el.rtlSetLocalX(100);
            expect(el.dom.style.right).toBe('100px');
        });

        it("should set the local x coordinate to an auto value", function() {
            el.rtlSetLocalX(null);
            expect(el.dom.style.right).toBe('auto');
        });
    });

    describe("rtlSetLocalXY", function() {
        describe("x and y as separate parameters", function() {
            it("should set only the local x coordinate to a pixel value", function() {
                el.rtlSetLocalXY(100);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('7px');
            });

            it("should set only the local x coordinate to an auto value", function() {
                el.rtlSetLocalXY(null);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('7px');
            });

            it("should set only the local y coordinate to a pixel value", function() {
                el.rtlSetLocalXY(undefined, 100);
                expect(el.dom.style.right).toBe('6px');
                expect(el.dom.style.top).toBe('100px');
            });

            it("should set only the local y coordinate to an auto value", function() {
                el.rtlSetLocalXY(undefined, null);
                expect(el.dom.style.right).toBe('6px');
                expect(el.dom.style.top).toBe('auto');
            });

            it("should set pixel x and pixel y", function() {
                el.rtlSetLocalXY(100, 200);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('200px');
            });

            it("should set pixel x and auto y", function() {
                el.rtlSetLocalXY(100, null);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('auto');
            });

            it("should set auto x and pixel y", function() {
                el.rtlSetLocalXY(null, 100);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('100px');
            });

            it("should set auto x and auto y", function() {
                el.rtlSetLocalXY(null, null);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('auto');
            });
        });

        describe("x and y as array parameter", function() {
            it("should set only the local x coordinate to a pixel value", function() {
                el.rtlSetLocalXY([100]);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('7px');
            });

            it("should set only the local x coordinate to an auto value", function() {
                el.rtlSetLocalXY([null]);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('7px');
            });

            it("should set only the local y coordinate to a pixel value", function() {
                el.rtlSetLocalXY([undefined, 100]);
                expect(el.dom.style.right).toBe('6px');
                expect(el.dom.style.top).toBe('100px');
            });

            it("should set only the local y coordinate to an auto value", function() {
                el.rtlSetLocalXY([undefined, null]);
                expect(el.dom.style.right).toBe('6px');
                expect(el.dom.style.top).toBe('auto');
            });

            it("should set pixel x and pixel y", function() {
                el.rtlSetLocalXY([100, 200]);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('200px');
            });

            it("should set pixel x and auto y", function() {
                el.rtlSetLocalXY([100, null]);
                expect(el.dom.style.right).toBe('100px');
                expect(el.dom.style.top).toBe('auto');
            });

            it("should set auto x and pixel y", function() {
                el.rtlSetLocalXY([null, 100]);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('100px');
            });

            it("should set auto x and auto y", function() {
                el.rtlSetLocalXY([null, null]);
                expect(el.dom.style.right).toBe('auto');
                expect(el.dom.style.top).toBe('auto');
            });
        });
    });
});
