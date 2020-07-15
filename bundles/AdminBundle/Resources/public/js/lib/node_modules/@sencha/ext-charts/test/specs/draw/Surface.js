topSuite("Ext.draw.Surface", function() {
    describe('add', function() {
        it("should not add the same sprite to the surface twice", function() {
            var surface = new Ext.draw.Surface({}),
                sprite = new Ext.draw.sprite.Rect({});

            surface.add(sprite);
            surface.add(sprite);

            expect(surface.getItems().length).toEqual(1);
            surface.removeAll(true);

            expect(surface.getItems().length).toEqual(0);

            surface.add([sprite, sprite]);
            expect(surface.getItems().length).toEqual(0);
            surface.destroy();
        });

        it("should remove the sprite from the old surface", function() {
            var surface1 = new Ext.draw.Surface({}),
                surface2 = new Ext.draw.Surface({}),
                sprite = new Ext.draw.sprite.Rect({});

            surface1.add(sprite);
            surface2.add(sprite);

            expect(surface1.getItems().length).toEqual(0);
            expect(surface2.getItems().length).toEqual(1);
            expect(surface2.get(0)).toBe(sprite);

            Ext.destroy(sprite, surface1, surface2);
        });

        it("should set the sprite's 'parent' and 'surface' configs to itself", function() {
            var sprite = new Ext.draw.sprite.Rect(),
                surface = new Ext.draw.Surface();

            surface.add(sprite);
            expect(sprite.getParent()).toBe(surface);
            expect(sprite.getSurface()).toBe(surface);

            surface.destroy();
        });
    });

    describe('get', function() {
        var surface, result;

        beforeEach(function() {
            surface = new Ext.draw.Surface({
                items: [
                    {
                        type: 'rect',
                        id: 'sprite1'
                    },
                    {
                        type: 'text',
                        id: 'sprite2'
                    }
                ]
            });
        });

        afterEach(function() {
            surface.destroy();
        });

        it("should be able to get a sprite by id", function() {
            result = surface.get('sprite1');
            expect(result.isSprite).toBe(true);
            expect(result.type).toBe('rect');

            result = surface.get('sprite2');
            expect(result.isSprite).toBe(true);
            expect(result.type).toBe('text');
        });

        it("should be able to get a sprite by index", function() {
            result = surface.get(0);
            expect(result.isSprite).toBe(true);
            expect(result.type).toBe('rect');

            result = surface.get(1);
            expect(result.isSprite).toBe(true);
            expect(result.type).toBe('text');
        });
    });

    describe('remove', function() {
        var oldClearPrototype;

        beforeEach(function() {
            oldClearPrototype = Ext.Base.prototype.clearPrototypeOnDestroy;
            Ext.Base.prototype.clearPrototypeOnDestroy = false;
        });

        afterEach(function() {
            Ext.Base.prototype.clearPrototypeOnDestroy = oldClearPrototype;
        });

        it("should be able to remove the sprite (instance or id), should return removed sprite", function() {
            var givenId = 'testing',
                sprite = new Ext.draw.sprite.Rect({}),
                spriteId = new Ext.draw.sprite.Text({ id: givenId }),
                surface = new Ext.draw.Surface({}),
                result, id;

            surface.add(sprite, spriteId);
            expect(surface.getItems().length).toBe(2);

            id = sprite.getId();
            result = surface.remove(sprite);
            expect(result).toEqual(sprite);
            expect(sprite.destroyed).toBe(false);
            expect(surface.getItems().length).toBe(1);
            expect(surface.get(id)).toBe(undefined);

            result = surface.remove(givenId);
            expect(result).toEqual(spriteId);
            expect(spriteId.destroyed).toBe(false);
            expect(surface.getItems().length).toBe(0);
            expect(surface.get(givenId)).toBe(undefined);

            surface.destroy();
            sprite.destroy();
            spriteId.destroy();
        });

        it("should be able to destroy the sprite (instance or id) in the process, should return destroyed sprite", function() {
            var sprite = new Ext.draw.sprite.Rect({}),
                spriteId = new Ext.draw.sprite.Text({ id: 'testing' }),
                surface = new Ext.draw.Surface({}),
                result;

            surface.add(sprite, spriteId);
            expect(surface.getItems().length).toBe(2);

            result = surface.remove(sprite, true);
            expect(result).toEqual(sprite);
            expect(result.destroyed).toBe(true);
            expect(surface.getItems().length).toBe(1);

            result = surface.remove('testing', true);
            expect(result).toEqual(spriteId);
            expect(result.destroyed).toBe(true);
            expect(surface.getItems().length).toBe(0);

            surface.destroy();
        });

        it("should return null if not given a sprite", function() {
            var surface = new Ext.draw.Surface({});

            function isNull(value) {
                return (null === surface.remove(value)) && (null === surface.remove(value, true));
            }

            expect(isNull(0)).toBe(true);
            expect(isNull(5)).toBe(true);
            expect(isNull(true)).toBe(true);
            expect(isNull(false)).toBe(true);
            expect(isNull(undefined)).toBe(true);
            expect(isNull(null)).toBe(true);
            expect(isNull('hello')).toBe(true);
            expect(isNull('')).toBe(true);
            expect(isNull({})).toBe(true);
            expect(isNull([])).toBe(true);

            surface.destroy();
        });

        it("if passed an already destroyed sprite, should return it without doing anything", function() {
            var deadSprite = new Ext.draw.sprite.Rect({}),
                surface = new Ext.draw.Surface({}),
                result;

            deadSprite.destroy();

            result = surface.remove(deadSprite);
            expect(result).toBe(deadSprite);

            result = surface.remove(deadSprite, true);
            expect(result).toBe(deadSprite);

            surface.destroy();
        });

        it("should be able to destroy (but not remove!) a sprite that belongs to another or no surface", function() {
            var surface1 = new Ext.draw.Surface({}),
                surface2 = new Ext.draw.Surface({}),
                sprite1 = new Ext.draw.sprite.Rect({}),
                sprite = new Ext.draw.sprite.Text({}),
                result;

            surface1.add(sprite1);

            result = surface2.remove(sprite1);
            expect(result).toBe(sprite1);
            expect(surface1.getItems()[0]).toEqual(sprite1);

            result = surface2.remove(sprite1, true);
            expect(result).toEqual(sprite1);
            expect(result.destroyed).toBe(true);
            expect(surface1.getItems().length).toBe(0);

            result = surface2.remove(sprite);
            expect(result).toEqual(sprite);
            expect(result.destroyed).toBe(false);

            result = surface2.remove(sprite, true);
            expect(result).toEqual(sprite);
            expect(result.destroyed).toBe(true);

            surface1.destroy();
            surface2.destroy();
        });
    });

    describe('destroy', function() {
        it("should fire the 'destroy' event", function() {
            var surface = new Ext.draw.Surface,
                isFired;

            surface.on('destroy', function() {
                isFired = true;
            });
            surface.destroy();

            expect(isFired).toBe(true);
        });
    });

    describe('waitFor', function() {
        var s1, s2, s3, s4;

        beforeEach(function() {
            s1 = new Ext.draw.Surface();
            s2 = new Ext.draw.Surface();
            s3 = new Ext.draw.Surface();
            s4 = new Ext.draw.Surface();
        });

        afterEach(function() {
            Ext.destroy(s1, s2, s3, s4);
        });

        it("should add the given surface to a list of current surface predecessors only once", function() {
            s1.waitFor(s2);
            expect(s1.predecessors.length).toBe(1);
            expect(s1.predecessors[0]).toEqual(s2);
        });

        it("should only increase own dirty predecessor counter if the given surface is dirty", function() {
            s1.waitFor(s2);
            expect(s1.dirtyPredecessorCount).toBe(0);
            s3.setDirty(true);
            s2.waitFor(s3);
            expect(s2.dirtyPredecessorCount).toBe(1);
        });

        it("should be able to wait for multiple surfaces", function() {
            s1.waitFor(s2);
            s1.waitFor(s3);
            s1.waitFor(s4);
            expect(s1.predecessors.length).toBe(3);
        });
    });

    describe("'dirty' config", function() {
        var s1, s2, s3, s4, s5;

        beforeEach(function() {
            s1 = new Ext.draw.Surface();
            s2 = new Ext.draw.Surface();
            s3 = new Ext.draw.Surface();
            s4 = new Ext.draw.Surface();
            s5 = new Ext.draw.Surface();
        });

        afterEach(function() {
            Ext.destroy(s1, s2, s3, s4, s5);
        });

        it("should not be dirty upon construction", function() {
            expect(s1.getDirty()).toBe(false);
        });

        it("should be dirty when items are removed but surface is not destroyed", function() {
            var sprite = new Ext.draw.sprite.Rect({});

            s1.add(sprite);
            s1.removeAll();

            expect(s1.getDirty()).toBe(true);

            s1.destroy();
        });

        it("should not be dirty when surface is destroyed", function() {
            var sprite = new Ext.draw.sprite.Rect({});

            s1.add(sprite);
            s1.setDirty(false);
            s1.destroy();

            expect(s1._dirty).toBe(false);
        });

        it("should increment dirtyPredecessorCount of all successors (not just immediate) when set to true", function() {
            s3.waitFor(s2);
            s5.waitFor(s4);
            s2.waitFor(s1);
            s4.waitFor(s1);
            // Order of rendering: s1 --> s2 --> s3
            //                        |
            //                        --> s4 --> s5
            s1.setDirty(true);
            expect(s2.dirtyPredecessorCount).toBe(1);
            expect(s3.dirtyPredecessorCount).toBe(1);
            expect(s4.dirtyPredecessorCount).toBe(1);
            expect(s5.dirtyPredecessorCount).toBe(1);
        });

        it("should decrement dirtyPredecessorCount of all immediate successors when set to false", function() {
            s3.waitFor(s2);
            s5.waitFor(s4);
            s2.waitFor(s1);
            s4.waitFor(s1);
            // Order of rendering: s1 --> s2 --> s3
            //                        |
            //                        --> s4 --> s5
            s1.setDirty(true);
            s1.setDirty(false);
            expect(s2.dirtyPredecessorCount).toBe(0);
            expect(s4.dirtyPredecessorCount).toBe(0);
            expect(s3.dirtyPredecessorCount).toBe(1);
            expect(s5.dirtyPredecessorCount).toBe(1);
        });

        it("should not affect dirtyPredecessorCount of successors if value hasn't changed", function() {
            s3.waitFor(s2);
            s5.waitFor(s4);
            s2.waitFor(s1);
            s4.waitFor(s1);
            // Order of rendering: s1 --> s2 --> s3
            //                        |
            //                        --> s4 --> s5
            s1.setDirty(false); // noop
            expect(s2.dirtyPredecessorCount).toBe(0);
            expect(s3.dirtyPredecessorCount).toBe(0);
            expect(s4.dirtyPredecessorCount).toBe(0);
            expect(s5.dirtyPredecessorCount).toBe(0);
            s1.setDirty(true); // increments dirtyPredecessorCount of all successors
            s1.setDirty(true); // noop
            expect(s2.dirtyPredecessorCount).toBe(1);
            expect(s3.dirtyPredecessorCount).toBe(1);
            expect(s4.dirtyPredecessorCount).toBe(1);
            expect(s5.dirtyPredecessorCount).toBe(1);
        });

        it("should make dirtyPredecessorCount reflect the actual number of immediate dirty predecessors", function() {
            s1.waitFor(s2);
            s1.waitFor(s3);
            s1.waitFor(s4);

            s2.setDirty(true);
            s3.setDirty(true);
            s4.setDirty(true);

            expect(s1.dirtyPredecessorCount).toBe(3);

            s3.setDirty(false);

            expect(s1.dirtyPredecessorCount).toBe(2);
        });
    });

});
