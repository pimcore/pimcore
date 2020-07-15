topSuite("Ext.draw.sprite.Sprite", ['Ext.draw.*'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe('setAttributes', function() {
        var draw;

        afterEach(function() {
            Ext.destroy(draw);
        });

        it('should filter out attributes properly', function() {
            // This actually tests many things: the setAttributes method of the sprite,
            // the abstract and Animation modifiers and the Animator class.
            var animationend;

            draw = new Ext.draw.Container({
                renderTo: document.body,
                width: 200,
                height: 200
            });
            var surface = draw.getSurface();

            var circle = new Ext.draw.sprite.Circle({
                r: 10,
                cx: 100,
                cy: 100,
                fillStyle: 'red',
                strokeStyle: 'none'
            });

            var animation = circle.getAnimation();

            animation.setDuration(250);
            animation.on('animationend', function() {
                animationend = true;
            });

            surface.add(circle);

            circle.setAttributes({
                r: 90
            });

            surface.renderFrame();

            waitsFor(function() {
                return animationend;
            });

            runs(function() {
                expect(circle.attr.r).toBe(90);
                animationend = false;
                circle.setAttributes({
                    r: 50
                });
                // The call below should compare the value being set with the target value
                // at the end of animation, not the current value, which is still 90.
                // If this is buggy, the attribute won't be set and the animation to 50
                // will be performed instead.
                circle.setAttributes({
                    r: 90
                });
                surface.renderFrame();
            });

            waitsFor(function() {
                return animationend;
            });

            runs(function() {
                expect(circle.attr.r).toBe(90);
            });
        });
    });

    describe('surface', function() {
        var surface;

        it('should remove itself from the old surface', function() {
            surface = new Ext.draw.Surface({
                items: {
                    type: 'rect',
                    id: 'rect',
                    x: 50,
                    y: 50,
                    width: 100,
                    height: 100,
                    fillStyle: 'orange'
                }
            });
            var sprite = surface.get('rect');

            expect(surface.getItems().length).toBe(1);
            sprite.setSurface(null);
            expect(surface.getItems().length).toBe(0);
        });

        afterEach(function() {
            Ext.destroy(surface);
        });
    });

    describe('transformation matrix calculation', function() {
        describe('default centers of scaling and rotation', function() {
            it('should apply transformation in the following order: scale, rotate, translate', function() {
                var theta = Math.PI / 2,
                    sin = Math.sin(theta),
                    cos = Math.cos(theta),
                    left = 100,
                    top = 100,
                    width = 100,
                    height = 100,
                    sx = 2,
                    sy = 0.5,
                    tx = 100,
                    ty = 50,
                    centerX = left + width / 2,
                    centerY = top + height / 2;

                var rect = new Ext.draw.sprite.Rect({
                    x: left,
                    y: top,
                    width: width,
                    height: height,
                    rotationRads: theta,
                    scalingX: sx,
                    scalingY: sy,
                    translationX: tx,
                    translationY: ty
                });

                var referenceMatrix = [
                    cos * sx, sin * sx,
                    -sin * sy, cos * sy,
                    cos * (centerX * (1 - sx) - centerX) - sin * (centerY * (1 - sy) - centerY) + centerX + tx,
                    sin * (centerX * (1 - sx) - centerX) + cos * (centerY * (1 - sy) - centerY) + centerY + ty
                ];

                rect.applyTransformations(true);
                expect(rect.attr.matrix.elements).toEqual(referenceMatrix);
            });
        });
        describe('custom centers of scaling and rotation', function() {
            it('should apply transformation in the following order: scale, rotate, translate', function() {
                var theta = Math.PI / 2,
                    sin = Math.sin(theta),
                    cos = Math.cos(theta),
                    left = 100,
                    top = 100,
                    width = 100,
                    height = 100,
                    sx = 2,
                    sy = 0.5,
                    tx = 100,
                    ty = 50,
                    scalingCenterX = 50,
                    scalingCenterY = 50,
                    rotationCenterX = 150,
                    rotationCenterY = 150;

                var rect = new Ext.draw.sprite.Rect({
                    x: left,
                    y: top,
                    width: width,
                    height: height,
                    rotationRads: theta,
                    scalingX: sx,
                    scalingY: sy,
                    translationX: tx,
                    translationY: ty,
                    rotationCenterX: rotationCenterX,
                    rotationCenterY: rotationCenterY,
                    scalingCenterX: scalingCenterX,
                    scalingCenterY: scalingCenterY
                });

                var referenceMatrix = [
                    cos * sx, sin * sx,
                    -sin * sy, cos * sy,
                    cos * (scalingCenterX * (1 - sx) - rotationCenterX) - sin * (scalingCenterY * (1 - sy) - rotationCenterY) + rotationCenterX + tx,
                    sin * (scalingCenterX * (1 - sx) - rotationCenterX) + cos * (scalingCenterY * (1 - sy) - rotationCenterY) + rotationCenterY + ty
                ];

                rect.applyTransformations(true);
                expect(rect.attr.matrix.elements).toEqual(referenceMatrix);
            });
        });
    });

    describe('setTransform', function() {
        // This is a result of scaling by (2.5, 7.5), rotating by Math.PI/4 and translating by (3,4).
        var elements = [1.76776695, 1.76776695, -5.30330086, 5.30330086, 3, 4],
            sprite;

        beforeEach(function() {
            sprite = new Ext.draw.sprite.Rect();
        });

        afterEach(function() {
            Ext.destroy(sprite);
        });

        it("should use the given elements for the transformation matrix of the sprite", function() {
            sprite.setTransform(elements);
            var matrixElements = sprite.attr.matrix.elements;

            expect(matrixElements).toEqual(elements);
        });
        it("should mark the sprite and its parent as dirty", function() {
            var drawContainer = new Ext.draw.Container({
                renderTo: Ext.getBody(),
                width: 200,
                height: 200
            });

            var surface = drawContainer.getSurface();

            expect(surface.getDirty()).toBe(false);
            surface.add(sprite);
            expect(surface.getDirty()).toBe(true);
            surface.renderFrame();
            expect(surface.getDirty()).toBe(false);
            sprite.setTransform(elements);
            expect(sprite.attr.dirty).toBe(true);
            expect(sprite.getParent().getDirty()).toBe(true);

            drawContainer.destroy();
        });
        it("should properly calculate the inverse matrix from the given matrix", function() {
            sprite.setTransform(elements);
            var inverseMatrixElements = sprite.attr.inverseMatrix.elements,
                precision = 8;

            expect(inverseMatrixElements[0]).toBeCloseTo(0.28284271, precision);
            expect(inverseMatrixElements[1]).toBeCloseTo(-0.0942809, precision);
            expect(inverseMatrixElements[2]).toBeCloseTo(0.28284271, precision);
            expect(inverseMatrixElements[3]).toBeCloseTo(0.0942809, precision);
            expect(inverseMatrixElements[4]).toBeCloseTo(-1.97989899, precision);
            expect(inverseMatrixElements[5]).toBeCloseTo(-0.0942809, precision);
        });
        it("should mark bbox transform as dirty", function() {
            sprite.setTransform(elements);
            expect(sprite.attr.bbox.transform.dirty).toBe(true);
        });
        it("should not update the transformation attributes by default", function() {
            var attr = sprite.attr,
                rotationRads = attr.rotationRads,
                rotationCenterX = attr.rotationCenterX,
                rotationCenterY = attr.rotationCenterY,
                scalingX = attr.scalingX,
                scalingY = attr.scalingY,
                scalingCenterX = attr.scalingCenterX,
                scalingCenterY = attr.scalingCenterY,
                translationX = attr.translationX,
                translationY = attr.translationY;

            sprite.setTransform(elements);

            expect(attr.rotationRads).toEqual(rotationRads);
            expect(attr.rotationCenterX).toEqual(rotationCenterX);
            expect(attr.rotationCenterY).toEqual(rotationCenterY);
            expect(attr.scalingX).toEqual(scalingX);
            expect(attr.scalingY).toEqual(scalingY);
            expect(attr.scalingCenterX).toEqual(scalingCenterX);
            expect(attr.scalingCenterY).toEqual(scalingCenterY);
            expect(attr.translationX).toEqual(translationX);
            expect(attr.translationY).toEqual(translationY);
        });
        it("should update the transformation attributes, if explicitly asked", function() {
            var attr = sprite.attr,
                precision = 8;

            sprite.setTransform(elements, true);

            expect(attr.rotationRads).toBeCloseTo(Math.PI / 4, precision);
            expect(attr.rotationCenterX).toEqual(0);
            expect(attr.rotationCenterY).toEqual(0);
            expect(attr.scalingX).toBeCloseTo(2.5, precision);
            expect(attr.scalingY).toBeCloseTo(7.5, precision);
            expect(attr.scalingCenterX).toEqual(0);
            expect(attr.scalingCenterY).toEqual(0);
            expect(attr.translationX).toEqual(3);
            expect(attr.translationY).toEqual(4);
        });
        it("should not modify the given array", function() {
            sprite.setTransform(elements);
            sprite.attr.matrix.rotate(Math.PI / 4);

            expect(elements).toEqual([1.76776695, 1.76776695, -5.30330086, 5.30330086, 3, 4]);
        });
        it("should return the sprite itself", function() {
            var result = sprite.transform([1, 0, 0, 1, 100, 100]);

            expect(result).toEqual(sprite);
        });
    });

    describe('resetTransform', function() {
        var spriteConfig = {
            type: 'rect',
            x: 0,
            y: 0,
            width: 100,
            height: 100,
            rotationCenterX: 0,
            rotationCenterY: 0,
            rotationRads: Math.PI / 3,
            scalingCenterX: 0,
            scalingCenterY: 0,
            scalingX: 2,
            scalingY: 3,
            translationX: 50,
            translationY: 50
        };

        it("should mark the sprite and its parent as dirty", function() {
            var drawContainer = new Ext.draw.Container({
                renderTo: Ext.getBody(),
                width: 200,
                height: 200
            });

            var surface = drawContainer.getSurface();

            expect(surface.getDirty()).toBe(false);
            var sprite = surface.add(spriteConfig);

            expect(surface.getDirty()).toBe(true);
            surface.renderFrame();
            expect(surface.getDirty()).toBe(false);
            sprite.resetTransform();
            expect(sprite.attr.dirty).toBe(true);
            expect(sprite.getParent().getDirty()).toBe(true);

            drawContainer.destroy();
        });

        it("should reset the transformation matrix and its reverse to the identity matrix", function() {
            var sprite = new Ext.draw.sprite.Rect(spriteConfig),
                identityMatrixElements = [1, 0, 0, 1, 0, 0];

            sprite.applyTransformations(true);
            expect(sprite.attr.matrix.elements).not.toEqual(identityMatrixElements);
            sprite.resetTransform();

            expect(sprite.attr.matrix.elements).toEqual(identityMatrixElements);
            expect(sprite.attr.inverseMatrix.elements).toEqual(identityMatrixElements);

            sprite.destroy();
        });
        it("should return the sprite itself", function() {
            var sprite = new Ext.draw.sprite.Rect(),
                result = sprite.transform([1, 0, 0, 1, 100, 100]);

            expect(result).toEqual(sprite);

            sprite.destroy();
        });
    });

    describe('transform', function() {
        it("should multiply the given matrix with the current transformation matrix", function() {
            var sprite = new Ext.draw.sprite.Rect(),
                precision = 12;

            sprite.attr.matrix.elements = [1, 2, 3, 4, 5, 6];
            sprite.transform([1, 2, 3, 4, 5, 6]);

            expect(sprite.attr.matrix.elements).toEqual([7, 10, 15, 22, 28, 40]);

            var inverseMatrixElements = sprite.attr.inverseMatrix.elements;

            expect(inverseMatrixElements[0]).toBeCloseTo(5.5, precision);
            expect(inverseMatrixElements[1]).toBeCloseTo(-2.5, precision);
            expect(inverseMatrixElements[2]).toBeCloseTo(-3.75, precision);
            expect(inverseMatrixElements[3]).toBeCloseTo(1.75, precision);
            expect(inverseMatrixElements[4]).toBeCloseTo(-4, precision);
            expect(inverseMatrixElements[5]).toBeCloseTo(0, precision);
        });
        it("should pre-multiply the current matrix with the given matrix", function() {
            var sprite = new Ext.draw.sprite.Rect(),
                scale = [2, 0, 0, 3, 0, 0],
                translate = [1, 0, 0, 1, 100, 100],
                p = [2, 4],
                tp;

            // Initially, sprite's matrix is identity matrix.

            // First scale the grid, then translate.
            // S * T * I = identity.prepend(translate).prepend(scale)
            sprite.transform(translate).transform(scale);
            expect(sprite.attr.matrix.elements).toEqual([2, 0, 0, 3, 200, 300]);
            tp = sprite.attr.matrix.transformPoint(p);
            // Transformed point in original grid coordinates.
            expect(tp).toEqual([204, 312]);

            sprite.resetTransform();

            // First translate the grid, then scale.
            // T * S * I = identity.prepend(scale).prepend(translate)
            sprite.transform(scale).transform(translate);
            expect(sprite.attr.matrix.elements).toEqual([2, 0, 0, 3, 100, 100]);
            tp = sprite.attr.matrix.transformPoint(p);
            expect(tp).toEqual([104, 112]);

            sprite.destroy();
        });
        it("should return the sprite itself", function() {
            var sprite = new Ext.draw.sprite.Rect(),
                result = sprite.transform([1, 0, 0, 1, 100, 100]);

            expect(result).toEqual(sprite);

            sprite.destroy();
        });
    });

    describe('remove', function() {
        it("should remove itself from the surface, returning itself or null (if already removed)", function() {
            var surface = new Ext.draw.Surface({}),
                sprite = new Ext.draw.sprite.Rect({}),
                id = sprite.getId(),
                result;

            surface.add(sprite);
            result = sprite.remove();

            expect(surface.getItems().length).toBe(0);
            expect(surface.get(id)).toBe(undefined);
            expect(result).toEqual(sprite);

            result = sprite.remove(); // sprite with no surface, expect not to throw
            expect(result).toBe(null);

            sprite.destroy();
            surface.destroy();
        });
    });

    describe('destroy', function() {
        it("should remove itself from the surface", function() {
            var surface = new Ext.draw.Surface({}),
                sprite = new Ext.draw.sprite.Rect({}),
                id = sprite.getId();

            surface.add(sprite);
            sprite.destroy();

            expect(surface.getItems().length).toBe(0);
            expect(surface.get(id)).toBe(undefined);

            surface.destroy();
        });
    });

    describe("isVisible", function() {
        var none = 'none',
            rgba_none = 'rgba(0,0,0,0)',
            sprite, surface, container;

        beforeEach(function() {
            container = new Ext.draw.Container({
                renderTo: Ext.getBody()
            });
            surface = new Ext.draw.Surface();
            sprite = new Ext.draw.sprite.Rect({
                hidden: false,
                globalAlpha: 1,
                fillOpacity: 1,
                strokeOpacity: 1,
                fillStyle: 'red',
                strokeStyle: 'red'
            });
            surface.add(sprite);
            container.add(surface);
        });

        afterEach(function() {
            Ext.destroy(sprite, surface, container);
        });

        it("should return true if the sprite belongs to a visible parent, false otherwise", function() {
            expect(sprite.isVisible()).toBe(true);

            surface.remove(sprite);
            expect(sprite.isVisible()).toBe(false);

            var instancing = new Ext.draw.sprite.Instancing({
                template: sprite
            });

            surface.add(instancing);
            expect(sprite.isVisible()).toBe(true);

            instancing.destroy();
        });

        it("should return false if the sprite belongs to a parent that doesn't belong to a surface", function() {
            var instancing = new Ext.draw.sprite.Instancing({
                template: sprite
            });

            expect(sprite.isVisible()).toBe(false);
        });

        it("should return false in case the sprite is hidden", function() {
            sprite.hide();
            expect(sprite.isVisible()).toBe(false);
        });

        it("should return false in case the sprite has no fillStyle and strokeStyle, true otherwise", function() {
            sprite.setAttributes({
                fillStyle: none
            });
            expect(sprite.isVisible()).toBe(true);

            sprite.setAttributes({
                fillStyle: rgba_none
            });
            expect(sprite.isVisible()).toBe(true);

            sprite.setAttributes({
                fillStyle: 'red',
                strokeStyle: none
            });
            expect(sprite.isVisible()).toBe(true);

            sprite.setAttributes({
                strokeStyle: rgba_none
            });
            expect(sprite.isVisible()).toBe(true);

            sprite.setAttributes({
                fillStyle: none,
                strokeStyle: none
            });
            expect(sprite.isVisible()).toBe(false);

            sprite.setAttributes({
                fillStyle: none,
                strokeStyle: rgba_none
            });
            expect(sprite.isVisible()).toBe(false);

            sprite.setAttributes({
                fillStyle: rgba_none,
                strokeStyle: none
            });
            expect(sprite.isVisible()).toBe(false);

            sprite.setAttributes({
                fillStyle: rgba_none,
                strokeStyle: rgba_none
            });
            expect(sprite.isVisible()).toBe(false);
        });

        it("should return false if the globalAlpha attribute is zero", function() {
            sprite.setAttributes({
                globalAlpha: 0
            });
            expect(sprite.isVisible()).toBe(false);
        });

        it("should return false if both fill and stroke are completely transparent, true otherwise", function() {
            sprite.setAttributes({
                fillOpacity: 0,
                strokeOpacity: 0
            });
            expect(sprite.isVisible()).toBe(false);

            sprite.setAttributes({
                fillOpacity: 0,
                strokeOpacity: 0.01
            });
            expect(sprite.isVisible()).toBe(true);

            sprite.setAttributes({
                fillOpacity: 0.01,
                strokeOpacity: 0
            });
            expect(sprite.isVisible()).toBe(true);
        });
    });

    describe("hitTest", function() {
        var sprite, surface, container;

        beforeEach(function() {
            container = new Ext.draw.Container({
                renderTo: Ext.getBody()
            });
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
            "if the sprite is visible and its bounding box is hit", function() {
            // Testing hitTest method of the abstract Sprite class.
            // Even though, (10,10) is not inside the circle, it's inside it's bounding box.
            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [10, 10]);

            expect(result && result.sprite).toBe(sprite);
        });

        it("should return null, if the sprite's bounding box is hit, but the sprite is not visible", function() {
            var originalMethod = sprite.isVisible;

            // eslint-disable-next-line brace-style
            sprite.isVisible = function() { return false; };

            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [10, 10]);

            expect(result).toBe(null);
            sprite.isVisible = originalMethod;
        });

        it("should return null, if the sprite is visible, but it's bounding box is not hit", function() {
            var result = Ext.draw.sprite.Sprite.prototype.hitTest.call(sprite, [210, 210]);

            expect(result).toBe(null);
        });
    });

    describe("getAnimation", function() {
        it("should return the stored reference to the sprite's animation modifier", function() {
            var sprite = new Ext.draw.sprite.Rect();

            expect(sprite.getAnimation()).toEqual(sprite.modifiers.animation);
        });
    });

    describe("setAnimation", function() {
        it("should set the config of the Animation modifier of a sprite", function() {
            var sprite = new Ext.draw.sprite.Rect();

            var config = {
                duration: 2000,
                easing: 'bounceOut',
                customEasings: {
                    x: 'linear'
                },
                customDurations: {
                    y: 1000
                }
            };

            sprite.setAnimation(config);

            var actualConfig = sprite.modifiers.animation.getInitialConfig();

            expect(actualConfig.duration).toEqual(config.duration);
            expect(actualConfig.easing).toEqual(config.easing);
            expect(actualConfig.customEasings).toEqual(config.customEasings);
            expect(actualConfig.customDurations).toEqual(config.customDurations);
        });
    });

});
