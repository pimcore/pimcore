topSuite("Ext.draw.sprite.Path", ['Ext.draw.*'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("hitTest", function() {
        var sprite, surface, container;

        beforeEach(function() {
            container = new Ext.draw.Container();
            surface = new Ext.draw.Surface();
            sprite = new Ext.draw.sprite.Circle({
                hidden: false,
                globalAlpha: 1,
                fillOpacity: 1,
                strokeOpacity: 1,
                fillStyle: 'red',
                strokeStyle: 'red',
                r: 100,
                cx: 100,
                cy: 100
            });
            surface.add(sprite);
            container.add(surface);
        });

        afterEach(function() {
            Ext.destroy(sprite, surface, container);
        });

        it("should return an object with the 'sprite' property set to the sprite itself, " +
            "if the sprite is visible and its bounding box and path are hit", function() {
            var result = sprite.hitTest([90, 90]);

            expect(result && result.sprite).toBe(sprite);
        });

        it("should return null, if the sprite is visible, its bounding box is hit, but the path isn't", function() {
            var result = sprite.hitTest([10, 10]);

            expect(result).toBe(null);
        });

    });

});
