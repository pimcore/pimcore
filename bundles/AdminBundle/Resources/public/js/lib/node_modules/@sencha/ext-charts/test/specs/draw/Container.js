topSuite("Ext.draw.Container", function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("'sprites' config", function() {
        var container;

        afterEach(function() {
            Ext.destroy(container);
        });

        it("should accept sprite configs.", function() {
            container = new Ext.draw.Container({
                sprites: {
                    type: 'rect',
                    x: 10
                }
            });

            var sprite = container.getSprites()[0];

            expect(sprite.isSprite).toBe(true);
            expect(sprite.type).toBe('rect');
            expect(sprite.attr.x).toEqual(10);
        });

        it("should accept sprite instances.", function() {
            container = new Ext.draw.Container({
                sprites: new Ext.draw.sprite.Rect({
                    x: 10
                })
            });

            var sprite = container.getSprites()[0];

            expect(sprite.isSprite).toBe(true);
            expect(sprite.type).toBe('rect');
            expect(sprite.attr.x).toEqual(10);
        });

        it("should put sprites into the specified surface or the 'main' one.", function() {
            container = new Ext.draw.Container({
                sprites: {
                    type: 'rect',
                    surface: 'test',
                    x: 10
                }
            });

            var sprite = container.getSurface('test').getItems()[0];

            expect(sprite.isSprite).toBe(true);
            expect(sprite.type).toBe('rect');
            expect(sprite.attr.x).toEqual(10);
        });
    });
});
