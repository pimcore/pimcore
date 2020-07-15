/**
 * The container that holds and manages instances of the {@link Ext.draw.Surface}
 * in which {@link Ext.draw.sprite.Sprite sprites} are rendered.  Draw containers are 
 * used as the foundation for all of the chart classes but may also be created directly 
 * in order to create custom drawings.
 * 
 *     @example
 *     var drawContainer = Ext.create('Ext.draw.Container', {
 *         renderTo: Ext.getBody(),
 *         width:200,
 *         height:200,
 *         sprites: [{
 *             type: 'circle',
 *             fillStyle: '#79BB3F',
 *             r: 100,
 *             x: 100,
 *             y: 100
 *          }]
 *     });
 *
 *     // Uncomment to trigger a download of the painted circle.
 *     // drawContainer.download({
 *     //     filename: 'Circle',
 *     //     url: 'http://svg.sencha.io' // Default server the image data is sent to.
 *     // });
 *
 * In the previous example we created a draw container and configured it with a single 
 * sprite.  The *type* of the sprite is {@link Ext.draw.sprite.Circle circle}, so if you 
 * run this code you'll see a green circle.
 *
 * You can attach sprite event listeners to the draw container with the help of the
 * {@link Ext.draw.plugin.SpriteEvents} plugin.
 *
 * For more information on sprites, the core elements added to a draw container's 
 * surface, refer to the Ext.draw.sprite.Sprite documentation.
 * 
 * For more information on surfaces, the interface owned by the draw container used to 
 * manage all sprites, see the Ext.draw.Surface documentation.
 */
Ext.define('Ext.draw.Container', {
    extend: 'Ext.draw.ContainerBase',
    alternateClassName: 'Ext.draw.Component',
    xtype: 'draw',
    defaultType: 'surface',
    isDrawContainer: true,

    requires: [
        'Ext.draw.Surface',
        'Ext.draw.engine.Svg',
        'Ext.draw.engine.Canvas',
        'Ext.draw.gradient.GradientDefinition'
    ],
    /**
     * @cfg {String} [engine="Ext.draw.engine.Canvas"]
     * Defines the engine (type of surface) used to render draw container contents.  
     * 
     * The render engine is selected automatically depending on the platform used. Priority 
     * is given to the {@link Ext.draw.engine.Canvas} engine due to its performance advantage.
     *
     * You may also set the engine config to be `Ext.draw.engine.Svg` if so desired.
     */
    engine: 'Ext.draw.engine.Canvas',

    /**
     * @event spritemousemove
     * Fires when the mouse is moved on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritemouseup
     * Fires when a mouseup event occurs on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritemousedown
     * Fires when a mousedown event occurs on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritemouseover
     * Fires when the mouse enters a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritemouseout
     * Fires when the mouse exits a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spriteclick
     * Fires when a click event occurs on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritedblclick
     * Fires when a double click event occurs on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event spritetap
     * Fires when a tap event occurs on a sprite.
     * @param {Object} sprite
     * @param {Event} event
     */

    /**
     * @event bodyresize
     * Fires when the size of the draw container body changes.
     * @param {Object} size The object containing 'width' and 'height' of the draw container's body.
     */

    config: {
        cls: [
            Ext.baseCSSPrefix + 'draw-container',
            Ext.baseCSSPrefix + 'unselectable'
        ],

        /**
         * @cfg {Function} [resizeHandler]
         * The resize function that can be configured to have a behavior,
         * e.g. resize draw surfaces based on new draw container dimensions.
         * The `resizeHandler` function takes a single parameter -
         * the size object with `width` and `height` properties.
         *
         * **Note:** Since resize events trigger {@link #renderFrame} calls automatically,
         * return `false` from the resize function, if it also calls `renderFrame`,
         * to prevent double rendering.
         */
        resizeHandler: null,

        /**
         * @cfg {Object[]} sprites
         * Defines a set of sprites to be added to the drawContainer surface.
         *
         * For example:
         *
         *      sprites: [{
         *           type: 'circle',
         *           fillStyle: '#79BB3F',
         *           r: 100,
         *           x: 100,
         *           y: 100
         *      }]
         * 
         */
        sprites: null,

        /**
         * @cfg {Object[]} gradients
         * Defines a set of gradients that can be used as color properties
         * (fillStyle and strokeStyle, but not shadowColor) in sprites.
         * The gradients array is an array of objects with the following properties:
         * - **id** - string - The unique name of the gradient.
         * - **type** - string, optional - The type of the gradient. Available types are: 'linear',
         * 'radial'. Defaults to 'linear'.
         * - **angle** - number, optional - The angle of the gradient in degrees.
         * - **stops** - array - An array of objects with 'color' and 'offset' properties, where
         * 'offset' is a real number from 0 to 1.
         *
         * For example:
         *
         *     gradients: [{
         *         id: 'gradientId1',
         *         type: 'linear',
         *         angle: 45,
         *         stops: [{
         *             offset: 0,
         *             color: 'red'
         *         }, {
         *            offset: 1,
         *            color: 'yellow'
         *         }]
         *     }, {
         *        id: 'gradientId2',
         *        type: 'radial',
         *        stops: [{
         *            offset: 0,
         *            color: '#555',
         *        }, {
         *            offset: 1,
         *            color: '#ddd',
         *        }]
         *     }]
         *
         * Then the sprites can use 'gradientId1' and 'gradientId2' by setting the color attributes
         * to those ids, for example:
         *
         *     sprite.setAttributes({
         *         fillStyle: 'url(#gradientId1)',
         *         strokeStyle: 'url(#gradientId2)'
         *     });
         */
        gradients: [],

        /**
         * @cfg {String} downloadServerUrl
         * The default URL used by the {@link #download} method.
         */
        downloadServerUrl: undefined,

        touchAction: {
            panX: false,
            panY: false,
            pinchZoom: false,
            doubleTapZoom: false
        },

        /**
         * @private
         * @cfg {Object} surfaceZIndexes A map of surface type name to zIndex.
         * The z-indexes to use for the various types of surfaces.
         */
        surfaceZIndexes: {
            main: 1
        }
    },

    /**
     * @private
     * @property {String} [defaultDownloadServerUrl="http://svg.sencha.io"]
     * The default URL used by the {@link #download} method if the {@link #downloadServerUrl}
     * config wasn't set.
     * To override this globally, set the `Ext.draw.Container.prototype.defaultDownloadServerUrl`.
     */
    defaultDownloadServerUrl: 'http://svg.sencha.io',

    /**
     * @property {Array} [supportedFormats=["png", "pdf", "jpeg", "gif"]]
     * A list of export types supported by the server.
     * @private
     */
    supportedFormats: ['png', 'pdf', 'jpeg', 'gif'],

    supportedOptions: {
        version: Ext.isNumber,
        data: Ext.isString,
        format: function(format) {
            return Ext.Array.indexOf(this.supportedFormats, format) >= 0;
        },
        filename: Ext.isString,
        width: Ext.isNumber,
        height: Ext.isNumber,
        scale: Ext.isNumber,
        pdf: Ext.isObject,
        jpeg: Ext.isObject
    },

    initAnimator: function() {
        this.frameCallbackId = Ext.draw.Animator.addFrameCallback('renderFrame', this);
    },

    applyDownloadServerUrl: function(url) {
        var defaultUrl = this.defaultDownloadServerUrl;

        if (!url) {
            url = defaultUrl;

            //<debug>
            // Skip this warning when unit testing.
            if (!window.jasmine) {
                Ext.log.warn('Using Sencha\'s download server could expose your data and pose ' +
                             'a security risk. Please see Ext.draw.Container#download method ' +
                             'docs for more info. (component id=' + this.getId() + ')');
            }
            //</debug>
        }

        return url;
    },

    applyGradients: function(gradients) {
        var result = [],
            i, n, gradient, offset;

        if (!Ext.isArray(gradients)) {
            return result;
        }

        for (i = 0, n = gradients.length; i < n; i++) {
            gradient = gradients[i];

            if (!Ext.isObject(gradient)) {
                continue;
            }

            // ExtJS only supported linear gradients, so we didn't have to specify their type
            if (typeof gradient.type !== 'string') {
                gradient.type = 'linear';
            }

            if (gradient.angle) {
                gradient.degrees = gradient.angle;
                delete gradient.angle;
            }

            // Convert ExtJS stops object to Touch stops array
            if (Ext.isObject(gradient.stops)) {
                gradient.stops = (function(stops) {
                    var result = [],
                        stop;

                    for (offset in stops) {
                        stop = stops[offset];
                        stop.offset = offset / 100;
                        result.push(stop);
                    }

                    return result;
                })(gradient.stops);
            }

            result.push(gradient);
        }

        Ext.draw.gradient.GradientDefinition.add(result);

        return result;
    },

    applySprites: function(sprites) {
        var result, surface, sprite, i, ln;

        // Never update.
        if (!sprites) {
            return;
        }

        sprites = Ext.Array.from(sprites);
        result = [];

        for (i = 0, ln = sprites.length; i < ln; i++) {
            sprite = sprites[i];
            surface = sprite.surface;

            if (!(surface && surface.isSurface)) {
                if (Ext.isString(surface)) {
                    surface = this.getSurface(surface);
                    delete sprite.surface;
                }
                else {
                    surface = this.getSurface('main');
                }
            }

            sprite = surface.add(sprite);
            result.push(sprite);
        }

        return result;
    },

    resizeDelay: 500, // in milliseconds
    resizeTimerId: 0,
    lastResizeTime: null,

    /**
     * @private
     * @property
     * Last valid size.
     */
    size: null,

    /**
     * Triggers the {@link #resizeHandler} with the size of the draw container
     * element as the parameter.
     */
    handleResize: function(size, instantly) {
        // See the following:
        // Classic: Ext.draw.ContainerBase.reattachToBody
        //  Modern: Ext.draw.ContainerBase.initialize
        var me = this,
            el = me.element,
            resizeHandler = me.getResizeHandler() || me.defaultResizeHandler,
            resizeDelay = me.resizeDelay,
            lastResizeTime = me.lastResizeTime,
            defer, result;

        if (!el) {
            return;
        }

        size = size || el.getSize();

        if (!(size.width && size.height)) {
            return;
        }

        me.size = size;

        me.stopResizeTimer();

        // Only want to defer when multiple resize events happen in quick succession.
        // That way it doesn't feel luggy during an occasional resize, nor it's too straining
        // when continuously resizing.
        defer = !instantly && lastResizeTime && (Ext.Date.now() - lastResizeTime < resizeDelay);

        if (defer) {
            me.resizeTimerId = Ext.defer(me.handleResize, resizeDelay, me, [size, true]);

            return;
        }

        me.fireEvent('bodyresize', me, size);

        Ext.callback(resizeHandler, null, [size], 0, me);

        if (result !== false) {
            me.renderFrame();
        }

        me.lastResizeTime = Ext.Date.now();
    },

    /**
     * @private
     */
    stopResizeTimer: function() {
        if (this.resizeTimerId) {
            Ext.undefer(this.resizeTimerId);
            this.resizeTimerId = 0;
        }
    },

    defaultResizeHandler: function(size) {
        this.getItems().each(function(surface) {
            surface.setRect([0, 0, size.width, size.height]);
        });
    },

    /**
     * Get a surface by the given id or create one if it doesn't exist.
     * This will automatically call the {@link #resizeHandler}. Which
     * means that, if no custom resize handler has been provided, the
     * surface will be sized to match the container.
     * If the {@link #method!add} method is used, it is the responsibility
     * of the user to call the {@link #handleResize} method, to update
     * the size of all added surfaces.
     * @param {String} [id="main"]
     * @param {String} type
     * @return {Ext.draw.Surface}
     */
    getSurface: function(id, type) {
        var me = this,
            surfaces = me.getItems(),
            oldCount = surfaces.getCount(),
            zIndexes = me.getSurfaceZIndexes(),
            surface;

        id = id || 'main';
        type = type || id;

        surface = me.createSurface(id);

        if (type in zIndexes) {
            surface.element.setStyle('zIndex', zIndexes[type]);
        }

        if (surfaces.getCount() > oldCount) {
            // Immediately call resize handler of the draw container,
            // so that the newly created surface gets a size.
            me.handleResize(null, true);
        }

        return surface;
    },

    createSurface: function(id) {
        var me = this,
            surfaces = me.getItems(),
            surface;

        id = this.getId() + '-' + (id || 'main');
        surface = surfaces.get(id);

        if (!surface) {
            surface = me.add({ xclass: me.engine, id: id });
        }

        return surface;
    },

    /**
     * Render all the surfaces in the container.
     */
    renderFrame: function() {
        var me = this,
            surfaces = me.getItems(),
            i, ln, item;

        for (i = 0, ln = surfaces.length; i < ln; i++) {
            item = surfaces.items[i];

            if (item.isSurface) {
                item.renderFrame();
            }
        }
    },

    /**
     * @private
     * Returns a slice of the surfaces (items) array of the draw container,
     * optionally sorting them by zIndex.
     * Overridden in subclasses.
     */
    getSurfaces: function(sort) {
        var surfaces = Array.prototype.slice.call(this.items.items),
            zIndexes = this.getSurfaceZIndexes(),
            i, j, surface, zIndex;

        if (sort) {
            // Sort the surfaces by zIndex using insertion sort.
            for (j = 1; j < surfaces.length; j++) {
                surface = surfaces[j];
                zIndex = zIndexes[surface.type];
                i = j - 1;

                while (i >= 0 && zIndexes[surfaces[i].type] > zIndex) {
                    surfaces[i + 1] = surfaces[i];
                    i--;
                }

                surfaces[i + 1] = surface;
            }
        }

        return surfaces;
    },

    /**
     * Produces an image of the chart / drawing.
     * @param {String} [format] Possible options are 'image' (the method will return an 
     * Image object) and 'stream' (the method will return the image as a byte stream).  
     * If missing, the data URI of the drawing's (or chart's) image will be returned.
     * Note: for an SVG based drawing/chart in IE/Edge browsers the method will always
     * return SVG markup instead of a data URI, as 'img' elements won't accept a data
     * URI anyway in those browsers.
     * @return {Object}
     * @return {String} return.data Image element, byte stream or DataURL.
     * @return {String} return.type The type of the data (e.g. 'png' or 'svg').
     */
    getImage: function(format) {
        var size = this.bodyElement.getSize(),
            surfaces = this.getSurfaces(true),
            surface = surfaces[0],
            image, imageElement;

        if ((Ext.isIE || Ext.isEdge) && surface.isSVG) {
            // SVG data URLs don't work in IE/Edge as a source for an 'img' element,
            // so we need to render SVG the usual way.
            image = {
                data: surface.toSVG(size, surfaces),
                type: 'svg-markup'
            };
        }
        else {
            image = surface.flatten(size, surfaces);

            if (format === 'image') {
                imageElement = new Image();
                imageElement.src = image.data;
                image.data = imageElement;

                return image;
            }

            if (format === 'stream') {
                image.data = image.data.replace(/^data:image\/[^;]+/, 'data:application/octet-stream');

                return image;
            }
        }

        return image;
    },

    /**
     * Downloads an image or PDF of the chart / drawing or opens it in a separate 
     * browser tab/window if the download can't be triggered. The exact behavior is
     * platform and browser specific. For more consistent results on mobile devices use
     * the {@link #preview} method instead. This method doesn't work in IE8.
     *
     * Important: The default download mechanism sends image data to `http://svg.sencha.io`,
     * which is a server operated by Sencha. This can be changed by setting
     * the {@link #downloadServerUrl} config to the address of another server.
     *
     * You can deploy your own server by using the code from the `server` directory
     * in the Charts package. The server is Node.js based and uses PhantomJS to
     * generate images and PDFs from received data.
     *
     * The warning that the default download server is used can be suppressed
     * by explicitly setting the value of the {@link #downloadServerUrl} config
     * to `http://svg.sencha.io`.
     *
     * @param {Object} [config] The following config options are supported:
     *
     * @param {String} config.url The url to post the data to. Defaults to
     * the value of the {@link #downloadServerUrl} config.
     *
     * @param {String} config.format The format of image to export. See the
     * {@link #supportedFormats}. Defaults to 'png' on the Sencha IO server.
     * Note that you can't export to 'svg' format if the {@link Ext.draw.engine.Canvas Canvas}
     * {@link Ext.draw.Container#engine engine} is used.
     *
     * @param {Number} config.width A width to send to the server for
     * configuring the image width. Defaults to natural image width on
     * the Sencha IO server.
     *
     * @param {Number} config.height A height to send to the server for
     * configuring the image height. Defaults to natural image height on
     * the Sencha IO server.
     *
     * @param {String} config.filename The filename of the downloaded image.
     * Defaults to 'chart' on the Sencha IO server. The config.format is used
     * as a filename extension.
     *
     * @param {Number} config.scale The scaling of the downloaded image.
     * Defaults to 1 on the Sencha IO server. The server will try to determine the natural
     * size of the image unless the width/height configs have been set. If the
     * {@link Ext.draw.engine.Canvas Canvas} {@link Ext.draw.Container#engine engine} is
     * used the natural image size will depend on the value of the window.devicePixelRatio.
     * For example, for devices with devicePixelRatio of 2 the produced image will be
     * two times larger than for devices with devicePixelRatio of 1 for the same drawing.
     * This is done so that the users with devices with HiDPI screens get a downloaded
     * image that looks as crisp on their device as the original drawing.
     * If you want image size to be consistent across devices with different device
     * pixel ratios, you can set the value of this config to 1/devicePixelRatio.
     * This parameter is ignored by the Sencha IO server if config.format is set to 'svg'.
     *
     * @param {Object} config.pdf PDF specific options.
     * This config is only used if config.format is set to 'pdf'.
     * The given object should be in either this format:
     *
     *     {
     *       width: '200px',
     *       height: '300px',
     *       border: '0px'
     *     }
     *
     * or this format:
     *
     *     {
     *       format: 'A4',
     *       orientation: 'portrait',
     *       border: '1cm'
     *     }
     *
     * Supported dimension units are: 'mm', 'cm', 'in', 'px'. No unit means 'px'.
     * Supported formats are: 'A3', 'A4', 'A5', 'Legal', 'Letter', 'Tabloid'.
     * Orientation ('portrait', 'landscape') is optional and defaults to 'portrait'.
     *
     * @param {Object} config.jpeg JPEG specific options.
     * This config is only used if config.format is set to 'jpeg'.
     * The given object should be in this format:
     *
     *     {
     *       quality: 80
     *     }
     *
     * Where quality is an integer between 0 and 100.
     *
     * @return {Boolean} True if request was successfully sent to the server.
     */
    download: function(config) {
        var me = this,
            inputs = [],
            markup, name, value;

        if (Ext.isIE8) {
            return false;
        }

        config = config || {};
        config.version = 2;

        if (!config.data) {
            config.data = me.getImage().data;
        }

        for (name in config) {
            if (config.hasOwnProperty(name)) {
                value = config[name];

                if (name in me.supportedOptions) {
                    if (me.supportedOptions[name].call(me, value)) {
                        inputs.push({
                            tag: 'input',
                            type: 'hidden',
                            name: name,
                            value: Ext.String.htmlEncode(
                                Ext.isObject(value) ? Ext.JSON.encode(value) : value
                            )
                        });
                    }
                    //<debug>
                    else {
                        Ext.log.error('Invalid value for image download option "' + name +
                                      '": ' + value);
                    }
                    //</debug>
                }
                //<debug>
                else {
                    Ext.log.error('Invalid image download option: "' + name + '"');
                }
                //</debug>
            }
        }

        markup = Ext.dom.Helper.markup({
            tag: 'html',
            children: [
                { tag: 'head' },
                {
                    tag: 'body',
                    children: [
                        {
                            tag: 'form',
                            method: 'POST',
                            action: config.url || me.getDownloadServerUrl(),
                            children: inputs
                        },
                        {
                            tag: 'script',
                            type: 'text/javascript',
                            children: 'document.getElementsByTagName("form")[0].submit();'
                        }
                    ]
                }
            ]
        });

        window.open('', 'ImageDownload_' + Date.now()).document.write(markup);
    },

    /**
     * @method preview
     * Displays an image of a Ext.draw.Container on screen.
     * On mobile devices this lets users tap-and-hold to bring up the menu
     * with image saving options.
     * Notes:
     * - some browsers won't save the preview image if it's SVG based
     *   (i.e. generated from a draw container that uses 'Ext.draw.engine.Svg' engine);
     * - some platforms may not have the means of viewing successfully saved SVG images;
     * - this method does not work on IE8.
     */

    doDestroy: function() {
        var me = this,
            callbackId = me.frameCallbackId;

        if (callbackId) {
            Ext.draw.Animator.removeFrameCallback(callbackId);
        }

        me.stopResizeTimer();

        me.callParent();
    }

}, function() {
    if (location.search.match('svg')) {
        Ext.draw.Container.prototype.engine = 'Ext.draw.engine.Svg';
    }
    else if ((Ext.os.is.BlackBerry && Ext.os.version.getMajor() === 10) ||
             (Ext.browser.is.AndroidStock4 && (Ext.os.version.getMinor() === 1 ||
             Ext.os.version.getMinor() === 2 || Ext.os.version.getMinor() === 3))) {
        // http://code.google.com/p/android/issues/detail?id=37529
        Ext.draw.Container.prototype.engine = 'Ext.draw.engine.Svg';
    }
});
