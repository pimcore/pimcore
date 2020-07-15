topSuite('Ext.draw.modifier.Highlight', ['Ext.draw.*'], function() {
    describe('filterChanges', function() {
        var draw;

        afterEach(function() {
            Ext.destroy(draw);
        });

        // A visual test should be the most robust way to test this.
        // It's engine specific, but the Canvas engine is used by default on almost every platform,
        // except for IE8 and old (pre-Chrome) Android.
        (!Ext.isChrome ? xit : it)('should not delete properties from the changes object', function() {
            var side = 100;

            draw = new Ext.draw.Container({
                renderTo: document.body,
                engine: 'Ext.draw.engine.Canvas',
                width: side,
                height: side
            });
            var surface = draw.getSurface();

            var instancing = new Ext.draw.sprite.Instancing({
                template: {
                    type: 'square',
                    size: 4,
                    modifiers: 'highlight',
                    fillStyle: 'red',
                    translationX: 20,
                    translationY: 20
                }
            });

            instancing.add({
                translationX: 50,
                translationY: 50
            });
            // If something went wrong, the sprite's applyTransformations method
            // won't be called before the instance is rendered, and the instance
            // will appear at the origin. In this case the second square would render
            // at (0,0) as its center on the Canvas.
            instancing.add({
                translationX: 20,
                translationY: 20
            });
            surface.add(instancing);
            surface.renderFrame();

            var imageData = surface.canvases[0].dom.getContext('2d').getImageData(0, 0, side, side);

            // The data is an array of bytes, 4 bytes for each pixel (RGBA components).
            // First pixel should not be red. Meaning square's transform attributes are applied
            // correctly.
            expect(imageData.data[0]).toBe(0);
            expect(imageData.data[1]).toBe(0);
            expect(imageData.data[2]).toBe(0);
        });
    });
});
