/**
 * @class Ext.draw.sprite.Composite
 * 
 * Represents a group of sprites.
 * Composite's sprites are rendered in the order they've been added to the Composite.
 * The rendering order of composite sprites themselves is determined by the value of
 * their zIndex attribute, just like with any other sprite.
 * Every sprite that is added to the Composite is removed from whatever Surface/Composite
 * it belongs to.
 */
Ext.define('Ext.draw.sprite.Composite', {
    extend: 'Ext.draw.sprite.Sprite',
    alias: 'sprite.composite',
    type: 'composite',
    isComposite: true,

    config: {
        sprites: []
    },

    constructor: function(config) {
        this.sprites = [];
        this.map = {};
        this.callParent([config]);
    },

    /**
     * Adds sprite(s) to the composite.
     * @param {Ext.draw.sprite.Sprite/Ext.draw.sprite.Sprite[]/Object/Object[]} sprite
     * @return {Ext.draw.sprite.Sprite/Ext.draw.sprite.Sprite[]}
     */
    addSprite: function(sprite) {
        var i = 0,
            attr, results, oldTransformations;

        if (Ext.isArray(sprite)) {
            results = [];

            while (i < sprite.length) {
                results.push(this.addSprite(sprite[i++]));
            }

            return results;
        }

        if (sprite && sprite.type && !sprite.isSprite) {
            sprite = Ext.create('sprite.' + sprite.type, sprite);
        }

        if (!sprite || !sprite.isSprite || sprite.isComposite) {
            return null;
        }

        sprite.setSurface(null);
        sprite.setParent(this);

        attr = this.attr;
        oldTransformations = sprite.applyTransformations;

        sprite.applyTransformations = function(force) {
            if (sprite.attr.dirtyTransform) {
                attr.dirtyTransform = true;
                attr.bbox.plain.dirty = true;
                attr.bbox.transform.dirty = true;
            }

            oldTransformations.call(sprite, force);
        };

        this.sprites.push(sprite);
        this.map[sprite.id] = sprite.getId();
        attr.bbox.plain.dirty = true;
        attr.bbox.transform.dirty = true;

        return sprite;
    },

    /**
     * @deprecated 6.2.1 Use {@link #addSprite} instead.
     */
    add: function(sprite) {
        return this.addSprite(sprite);
    },

    removeSprite: function(sprite, isDestroy) {
        var me = this,
            id, isOwnSprite;

        if (sprite) {
            if (sprite.charAt) { // is String
                sprite = me.map[sprite];
            }

            if (!sprite || !sprite.isSprite) {
                return null;
            }

            if (sprite.destroyed || sprite.destroying) {
                return sprite;
            }

            id = sprite.getId();
            isOwnSprite = me.map[id];
            delete me.map[id];

            if (isDestroy) {
                sprite.destroy();
            }

            if (!isOwnSprite) {
                return sprite;
            }

            sprite.setParent(null);
            // sprite.setSurface(null);
            Ext.Array.remove(me.sprites, sprite);

            me.dirtyZIndex = true;
            me.setDirty(true);
        }

        return sprite || null;
    },

    /**
     * @deprecated 6.2.1 Use {@link #addSprite} instead.
     * Adds a list of sprites to the composite.
     * @param {Ext.draw.sprite.Sprite[]|Object[]|Ext.draw.sprite.Sprite|Object} sprites
     */
    addAll: function(sprites) {
        var i = 0;

        if (sprites.isSprite || sprites.type) {
            this.add(sprites);
        }
        else if (Ext.isArray(sprites)) {
            while (i < sprites.length) {
                this.add(sprites[i++]);
            }
        }
    },

    /**
     * Updates the bounding box of the composite, which contains the bounding box of all sprites
     * in the composite.
     */
    updatePlainBBox: function(plain) {
        var me = this,
            left = Infinity,
            right = -Infinity,
            top = Infinity,
            bottom = -Infinity,
            sprite, bbox, i, ln;

        for (i = 0, ln = me.sprites.length; i < ln; i++) {
            sprite = me.sprites[i];
            sprite.applyTransformations();
            bbox = sprite.getBBox();

            if (left > bbox.x) {
                left = bbox.x;
            }

            if (right < bbox.x + bbox.width) {
                right = bbox.x + bbox.width;
            }

            if (top > bbox.y) {
                top = bbox.y;
            }

            if (bottom < bbox.y + bbox.height) {
                bottom = bbox.y + bbox.height;
            }
        }

        plain.x = left;
        plain.y = top;
        plain.width = right - left;
        plain.height = bottom - top;
    },

    isVisible: function() {
        // Override the abstract Sprite's method.
        // Composite uses a simpler check, because it has no fill or stroke
        // style of its own, it just houses other sprites.
        var attr = this.attr,
            parent = this.getParent(),
            hasParent = parent && (parent.isSurface || parent.isVisible()),
            isSeen = hasParent && !attr.hidden && attr.globalAlpha;

        return !!isSeen;
    },

    /**
     * Renders all sprites contained in the composite to the surface.
     */
    render: function(surface, ctx, rect) {
        var me = this,
            attr = me.attr,
            mat = me.attr.matrix,
            sprites = me.sprites,
            ln = sprites.length,
            i = 0;

        mat.toContext(ctx);

        for (; i < ln; i++) {
            surface.renderSprite(sprites[i], rect);
        }

        //<debug>
        // eslint-disable-next-line vars-on-top
        var debug = attr.debug || me.statics().debug || Ext.draw.sprite.Sprite.debug;

        if (debug) {
            attr.inverseMatrix.toContext(ctx);

            if (debug.bbox) {
                me.renderBBox(surface, ctx);
            }
        }
        //</debug>
    },

    destroy: function() {
        var me = this,
            sprites = me.sprites,
            ln = sprites.length,
            i;

        for (i = 0; i < ln; i++) {
            sprites[i].destroy();
        }

        sprites.length = 0;

        me.callParent();
    }
});
