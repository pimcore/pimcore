topSuite("Ext.picker.Color", function() {
    var colorPicker,
        createPicker = function(config) {
            colorPicker = new Ext.picker.Color(Ext.apply({
                renderTo: Ext.getBody()
            }, config));
        };

    beforeEach(function() {
        this.addMatchers({
            toHaveSelected: function(color) {
                var el = this.actual.el.down('a.color-' + color, true);

                return Ext.fly(el).hasCls(colorPicker.selectedCls);
            }
        });
    });

    afterEach(function() {
        if (colorPicker) {
            colorPicker.destroy();
            colorPicker = null;
        }
    });

    describe("alternate class name", function() {
        it("should have Ext.ColorPalette as the alternate class name", function() {
            expect(Ext.picker.Color.prototype.alternateClassName).toEqual("Ext.ColorPalette");
        });

        it("should allow the use of Ext.ColorPalette", function() {
            expect(Ext.ColorPalette).toBeDefined();
        });
    });

    describe("initialisation", function() {
        beforeEach(function() {
            createPicker({
                value: "003300"
            });
        });

        it("should select the element corresponding to the initial value", function() {
            expect(colorPicker).toHaveSelected("003300");
        });

        it("should set the value", function() {
            expect(colorPicker.value).toBe("003300");
        });
    });

    describe("mouse click", function() {
        beforeEach(function() {
            var a, xy;

            createPicker();
            a = colorPicker.el.down('a.color-339966', true);
            xy = Ext.fly(a).getAnchorXY('c');

            jasmine.fireMouseEvent(a, "click", xy[0], xy[1]);
        });

        it("should select the element corresponding to the initial value", function() {
            expect(colorPicker).toHaveSelected("339966");
        });

        it("should set the value", function() {
            expect(colorPicker.value).toBe("339966");
        });
    });

    describe("select", function() {
        describe("when picker is rendered", function() {
            beforeEach(function() {
                createPicker();
            });

            it("should handle color with #", function() {
                colorPicker.select("#339966");

                expect(colorPicker).toHaveSelected("339966");
                expect(colorPicker.value).toBe("339966");
            });

            it("should handle color without #", function() {
                colorPicker.select("339966");

                expect(colorPicker).toHaveSelected("339966");
                expect(colorPicker.value).toBe("339966");
            });

            it("should be able to supress event", function() {
                spyOn(colorPicker, "fireEvent");
                colorPicker.select("#339966", true);

                expect(colorPicker.fireEvent).not.toHaveBeenCalled();
            });
        });

        describe("when picker isn't rendered", function() {
            beforeEach(function() {
                createPicker({
                    renderTo: undefined
                });
            });

            it("should handle color with #", function() {
                colorPicker.select("#339966");

                expect(colorPicker.value).toBe("339966");
            });

            it("should handle color without #", function() {
                colorPicker.select("339966");

                expect(colorPicker.value).toBe("339966");
            });

            it("should be able to supress event", function() {
                spyOn(colorPicker, "fireEvent");
                colorPicker.select("#339966", true);

                expect(colorPicker.fireEvent).not.toHaveBeenCalled();
            });
        });
    });

        describe("getValue", function() {
            beforeEach(function() {
                createPicker();
            });

            it("should return the value if a value was selected", function() {
                colorPicker.select("339966");

                expect(colorPicker.getValue()).toBe("339966");
            });

            it("should return null if no value was selected", function() {
                expect(colorPicker.getValue()).toBeNull();
            });
        });

});
