topSuite("Ext.slider.Tip", ['Ext.slider.Single'], function() {
    var slider, tip, thumb0, spaceEl,
        createSlider = function(config) {
            tip = new Ext.slider.Tip();

            spyOn(tip, "show").andCallThrough();
            spyOn(tip, "update").andCallThrough();

            // make enough room to display tip correctly
            spaceEl = Ext.getBody().createChild({});
            spaceEl.setHeight(100);
            slider = new Ext.slider.Single(Ext.apply({
                renderTo: Ext.getBody(),
                name: "test",
                width: 205,
                labelWidth: 0,
                minValue: 0,
                maxValue: 100,
                useTips: false,
                plugins: [tip],
                animate: false
            }, config));

            thumb0 = slider.thumbs[0];
    };

    afterEach(function() {
        if (slider) {
            slider.destroy();
        }

        spaceEl.destroy();
        slider = null;
    });

    describe("when thumb is dragged", function() {
        var thumbXY, thumbSize, tipXY, tipSize;

        beforeEach(function() {
            createSlider();
            var xy = thumb0.el.getXY();

            jasmine.fireMouseEvent(thumb0.el, 'mousedown', xy[0], xy[1] + 5);
            jasmine.fireMouseEvent(thumb0.el, 'mousemove', xy[0] + 50, xy[1] + 5);

            waitsFor(function() {
                return tip.el;
            });

            runs(function() {
                tipXY = tip.el.getXY();
                tipSize = tip.el.getSize();
                thumbXY = thumb0.el.getXY();
                thumbSize = thumb0.el.getSize();
                jasmine.fireMouseEvent(thumb0.el, 'mouseup', xy[0] + 50, xy[1] + 5);
            });
        });

        it("should show the tooltip", function() {
            expect(tip.show).toHaveBeenCalled();
        });

        it("should update the tooltip text", function() {
            expect(tip.update).toHaveBeenCalledWith(tip.getText(thumb0));
        });

        it("should align the tip to t-b?", function() {
            expect(tipXY[0] < thumbXY[0]).toBe(true);
            expect(tipXY[0] + tipSize.width > thumbXY[0] + thumbSize.width).toBe(true);
            expect(tipXY[1] - tip.offsets[1] + tipSize.height).toBe(thumbXY[1]);
        });

    });
});
