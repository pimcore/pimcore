topSuite("Ext.draw.sprite.Instancing", ['Ext.draw.*'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("'template' config", function() {
        it("should set the template's parent to the instancing sprite", function() {
            var template = new Ext.draw.sprite.Rect(),
                instancing = new Ext.draw.sprite.Instancing({
                    template: template
                });

            expect(template.getParent()).toBe(instancing);

            instancing.destroy();
        });

        it("should destroy the template when destroyed", function() {
            var template = new Ext.draw.sprite.Rect(),
                instancing = new Ext.draw.sprite.Instancing({
                    template: template
                });

            instancing.destroy();

            expect(instancing.destroyed).toBe(true);
        });
    });

    describe("'instances' config", function() {
        it("should create instances of the template from array of config objects", function() {
            var template = new Ext.draw.sprite.Circle({
                    cx: 200,
                    r: 60,
                    fillStyle: '#00ff00'
                }),
                instancing = new Ext.draw.sprite.Instancing({
                    template: template,
                    instances: [
                        {
                            cy: 150,
                            r: 30,
                            fillStyle: '#ff0000'
                        },
                        {
                            cy: 300
                        }
                    ]
                });

            expect(instancing.getCount()).toBe(2);
            expect(instancing.get(0).cx).toBe(200);
            expect(instancing.get(0).r).toBe(30);
            expect(instancing.get(1).fillStyle).toBe('#00ff00');

            instancing.destroy();
        });

        it("should destroy the template when destroyed", function() {
            var template = new Ext.draw.sprite.Rect(),
                instancing = new Ext.draw.sprite.Instancing({
                    template: template
                });

            instancing.destroy();

            expect(instancing.destroyed).toBe(true);
        });
    });

    describe("hitTest", function() {
        var sprite, instancingSprite, surface, container;

        beforeEach(function() {
            container = new Ext.draw.Container();
            surface = new Ext.draw.Surface();
            sprite = new Ext.draw.sprite.Circle({
                hidden: false,
                globalAlpha: 1,
                fillOpacity: 1,
                strokeOpacity: 1,
                fillStyle: 'red',
                strokeStyle: 'red'
            });
            instancingSprite = new Ext.draw.sprite.Instancing({
                template: sprite
            });
            surface.add(instancingSprite);
            container.add(surface);
        });

        afterEach(function() {
            Ext.destroy(sprite, instancingSprite, surface, container);
        });

        it("should return an object with the 'sprite' property set to the instancing sprite, " +
            "'template' property set to the instancing template, " +
            "'instance' property set to the attributes of the instance, " +
            "'index' property set to the index of the instance, " +
            "and 'isInstance' property set to true", function() {
            instancingSprite.add({
                r: 50,
                cx: 300,
                cy: 300
            });
            instancingSprite.add({
                r: 100,
                cx: 100,
                cy: 100
            });
            var result = instancingSprite.hitTest([90, 90]);

            expect(result.isInstance).toBe(true);
            expect(result.instance).toBe(instancingSprite.get(1));
            expect(result.index).toBe(1);
            expect(result.template).toBe(sprite);
            expect(result.sprite).toBe(instancingSprite);
        });

        it("should return null for hidden instances", function() {
            instancingSprite.add({
                r: 100,
                cx: 100,
                cy: 100,
                hidden: true
            });
            var result = instancingSprite.hitTest([90, 90]);

            expect(result).toBe(null);
        });

    });
});
