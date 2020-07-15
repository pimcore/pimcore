/**
 * @class Ext.draw.sprite.Image
 * @extends Ext.draw.sprite.Rect
 *
 * A sprite that represents an image.
 */
Ext.define('Ext.draw.sprite.Image', {
    extend: 'Ext.draw.sprite.Rect',
    alias: 'sprite.image',
    type: 'image',
    statics: {
        imageLoaders: {}
    },

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {String} [src=''] The image source of the sprite.
                 */
                src: 'string'
                /**
                 * @private
                 * @cfg {Number} radius
                 */
            },
            triggers: {
                src: 'src'
            },
            updaters: {
                src: 'updateSource'
            },
            defaults: {
                src: '',
                /**
                 * @cfg {Number} [width=null] The width of the image.
                 * For consistent image size on all devices the width must be explicitly set.
                 * Otherwise the natural image width devided by the device pixel ratio
                 * (for a crisp looking image) will be used as the width of the sprite.
                 */
                width: null,
                /**
                 * @cfg {Number} [height=null] The height of the image.
                 * For consistent image size on all devices the height must be explicitly set.
                 * Otherwise the natural image height devided by the device pixel ratio
                 * (for a crisp looking image) will be used as the height of the sprite.
                 */
                height: null
            }
        }
    },

    updateSurface: function(surface) {
        if (surface) {
            this.updateSource(this.attr);
        }
    },

    updateSource: function(attr) {
        var me = this,
            src = attr.src,
            surface = me.getSurface(),
            loadingStub = Ext.draw.sprite.Image.imageLoaders[src],
            width = attr.width,
            height = attr.height,
            imageLoader,
            i;

        if (!surface) {
            // First time this is called the sprite won't have a surface yet.
            return;
        }

        if (!loadingStub) {
            imageLoader = new Image();
            loadingStub = Ext.draw.sprite.Image.imageLoaders[src] = {
                image: imageLoader,
                done: false,
                pendingSprites: [me],
                pendingSurfaces: [surface]
            };
            imageLoader.width = width;
            imageLoader.height = height;

            imageLoader.onload = function() {
                var item;

                if (!loadingStub.done) {
                    loadingStub.done = true;

                    for (i = 0; i < loadingStub.pendingSprites.length; i++) {
                        item = loadingStub.pendingSprites[i];

                        if (!item.destroyed) {
                            item.setDirty(true);
                        }
                    }

                    for (i = 0; i < loadingStub.pendingSurfaces.length; i++) {
                        item = loadingStub.pendingSurfaces[i];

                        if (!item.destroyed) {
                            item.renderFrame();
                        }
                    }
                }
            };

            imageLoader.src = src;
        }
        else {
            Ext.Array.include(loadingStub.pendingSprites, me);
            Ext.Array.include(loadingStub.pendingSurfaces, surface);
        }
    },

    render: function(surface, ctx) {
        var me = this,
            attr = me.attr,
            mat = attr.matrix,
            src = attr.src,
            x = attr.x,
            y = attr.y,
            width = attr.width,
            height = attr.height,
            loadingStub = Ext.draw.sprite.Image.imageLoaders[src],
            image;

        if (loadingStub && loadingStub.done) {
            mat.toContext(ctx);
            image = loadingStub.image;
            ctx.drawImage(
                image, x, y,
                width || (image.naturalWidth || image.width) / surface.devicePixelRatio,
                height || (image.naturalHeight || image.height) / surface.devicePixelRatio
            );
        }

        //<debug>
        // eslint-disable-next-line vars-on-top
        var debug = attr.debug || this.statics().debug || Ext.draw.sprite.Sprite.debug;

        if (debug && debug.bbox) {
            this.renderBBox(surface, ctx);
        }
        //</debug>
    },

    /**
     * @private
     */
    isVisible: function() {
        var attr = this.attr,
            parent = this.getParent(),
            hasParent = parent && (parent.isSurface || parent.isVisible()),
            isSeen = hasParent && !attr.hidden && attr.globalAlpha;

        return !!isSeen;
    }
});
