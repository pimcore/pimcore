topSuite("Ext.draw.sprite.Composite", ['Ext.draw.sprite.*'], function() {
    var proto = Ext.draw.sprite.Text.prototype;

    describe('add', function() {
        var draw;

        it('should remove added sprites from their surface', function() {
            draw = new Ext.draw.Container({
                renderTo: document.body,
                width: 200,
                height: 200,

                sprites: [
                    {
                        type: 'rect',
                        id: 'rect',
                        x: 50,
                        y: 50,
                        width: 100,
                        height: 100,
                        fillStyle: 'orange'
                    },
                    {
                        type: 'circle',
                        id: 'circle',
                        cx: 100,
                        cy: 100,
                        r: 50,
                        fillStyle: 'red'
                    }
                ]
            });

            var mainSurface = draw.getSurface();

            var composite = new Ext.draw.sprite.Composite();

            expect(mainSurface.getItems().length).toBe(2);
            composite.add(mainSurface.get('rect'));
            composite.add(mainSurface.get('circle'));
            mainSurface.add(composite);
            expect(mainSurface.getItems().length).toBe(1);
            expect(mainSurface.get(0).isComposite).toBe(true);
        });

        afterEach(function() {
            Ext.destroy(draw);
        });
    });

    describe('destroy', function() {
        it("should destroy composite's children", function() {
            var composite = new Ext.draw.sprite.Composite({});

            composite.add({
                type: 'text',
                text: 'hello'
            });

            composite.add({
                type: 'rect'
            });

            var sprites = composite.sprites,
                child = sprites[1];

            composite.destroy();

            expect(sprites.length).toEqual(0);
            expect(child.destroyed).toEqual(true);
        });
    });
});
