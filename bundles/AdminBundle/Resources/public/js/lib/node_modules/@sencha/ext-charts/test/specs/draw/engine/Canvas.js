topSuite("Ext.draw.engine.Canvas", ['Ext.draw.Container'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe('surface splitting', function() {
        (Ext.isAndroid ? xit : it)("should split the surface into canvas tiles vertically and horizontally based on splitThreshold", function() {
            var side = 400,
                threshold = 200,
                proto = Ext.draw.engine.Canvas.prototype,
                originalThreshold = proto.splitThreshold;

            proto.splitThreshold = threshold;

            var draw = new Ext.draw.Container({
                renderTo: Ext.getBody(),
                engine: 'Ext.draw.engine.Canvas',
                width: side,
                height: side
            });

            var surface = draw.getSurface();

            var expectedCanvasCount = Math.pow(Math.ceil((side * (window.devicePixelRatio || window.screen.deviceXDPI / window.screen.logicalXDPI)) / threshold), 2);

            expect(surface.bodyElement.select('canvas').elements.length).toBe(expectedCanvasCount);
            proto.splitThreshold = originalThreshold;
            Ext.destroy(draw);
        });
    });
});
