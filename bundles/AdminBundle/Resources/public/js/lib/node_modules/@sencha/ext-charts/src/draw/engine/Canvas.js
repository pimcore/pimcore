/* global G_vmlCanvasManager */
/**
 * Provides specific methods to draw with 2D Canvas element.
 */
Ext.define('Ext.draw.engine.Canvas', {
    extend: 'Ext.draw.Surface',
    isCanvas: true,

    requires: [
        //<feature legacyBrowser>
        'Ext.draw.engine.excanvas',
        //</feature>

        'Ext.draw.Animator',
        'Ext.draw.Color'
    ],

    config: {
        /**
         * @cfg {Boolean} highPrecision
         * True to have the Canvas use JavaScript Number instead of single precision floating point
         * for transforms.
         *
         * For example, when using data with big numbers to plot line series, the transformation
         * matrix of the canvas will have big elements. Due to the implementation of the SVGMatrix,
         * the elements are represented by 32-bits floats, which will work incorrectly.
         * To compensate for that, we enable the canvas context to perform all the transformations
         * in JavaScript.
         *
         * Do not use this if you are not encountering 32-bit floating point errors problem,
         * since this will result in a performance penalty.
         */
        highPrecision: false
    },

    statics: {
        contextOverrides: {
            /**
             * @ignore
             */
            setGradientBBox: function(bbox) {
                this.bbox = bbox;
            },

            /**
             * Fills the subpaths of the current default path or the given path with the current
             * fill style.
             * @ignore
             */
            fill: function() {
                var fillStyle = this.fillStyle,
                    fillGradient = this.fillGradient,
                    fillOpacity = this.fillOpacity,
                    alpha = this.globalAlpha,
                    bbox = this.bbox;

                if (fillStyle !== Ext.util.Color.RGBA_NONE && fillOpacity !== 0) {
                    if (fillGradient && bbox) {
                        this.fillStyle = fillGradient.generateGradient(this, bbox);
                    }

                    if (fillOpacity !== 1) {
                        this.globalAlpha = alpha * fillOpacity;
                    }

                    this.$fill();

                    if (fillOpacity !== 1) {
                        this.globalAlpha = alpha;
                    }

                    if (fillGradient && bbox) {
                        this.fillStyle = fillStyle;
                    }
                }
            },

            /**
             * Strokes the subpaths of the current default path or the given path with the current
             * stroke style.
             * @ignore
             */
            stroke: function() {
                var strokeStyle = this.strokeStyle,
                    strokeGradient = this.strokeGradient,
                    strokeOpacity = this.strokeOpacity,
                    alpha = this.globalAlpha,
                    bbox = this.bbox;

                if (strokeStyle !== Ext.util.Color.RGBA_NONE && strokeOpacity !== 0) {
                    if (strokeGradient && bbox) {
                        this.strokeStyle = strokeGradient.generateGradient(this, bbox);
                    }

                    if (strokeOpacity !== 1) {
                        this.globalAlpha = alpha * strokeOpacity;
                    }

                    this.$stroke();

                    if (strokeOpacity !== 1) {
                        this.globalAlpha = alpha;
                    }

                    if (strokeGradient && bbox) {
                        this.strokeStyle = strokeStyle;
                    }
                }
            },

            /**
             * @ignore
             */
            fillStroke: function(attr, transformFillStroke) {
                var ctx = this,
                    fillStyle = this.fillStyle,
                    fillOpacity = this.fillOpacity,
                    strokeStyle = this.strokeStyle,
                    strokeOpacity = this.strokeOpacity,
                    shadowColor = ctx.shadowColor,
                    shadowBlur = ctx.shadowBlur,
                    none = Ext.util.Color.RGBA_NONE;

                if (transformFillStroke === undefined) {
                    transformFillStroke = attr.transformFillStroke;
                }

                if (!transformFillStroke) {
                    attr.inverseMatrix.toContext(ctx);
                }

                if (fillStyle !== none && fillOpacity !== 0) {
                    ctx.fill();
                    ctx.shadowColor = none;
                    ctx.shadowBlur = 0;
                }

                if (strokeStyle !== none && strokeOpacity !== 0) {
                    ctx.stroke();
                }

                ctx.shadowColor = shadowColor;
                ctx.shadowBlur = shadowBlur;
            },

            /**
             * 2D Canvas context in IE (up to IE10, inclusive) doesn't support
             * the setLineDash method and the lineDashOffset property.
             * @param dashList An even number of non-negative numbers specifying a dash list.
             */
            setLineDash: function(dashList) {
                if (this.$setLineDash) {
                    this.$setLineDash(dashList);
                }
            },

            getLineDash: function() {
                if (this.$getLineDash) {
                    return this.$getLineDash();
                }
            },

            /**
             * Adds points to the subpath such that the arc described by the circumference of the
             * ellipse described by the arguments, starting at the given start angle and ending at
             * the given end angle, going in the given direction (defaulting to clockwise), is added
             * to the path, connected to the previous point by a straight line.
             * @ignore
             */
            ellipse: function(cx, cy, rx, ry, rotation, start, end, anticlockwise) {
                var cos = Math.cos(rotation),
                    sin = Math.sin(rotation);

                this.transform(cos * rx, sin * rx, -sin * ry, cos * ry, cx, cy);
                this.arc(0, 0, 1, start, end, anticlockwise);
                this.transform(
                    cos / rx, -sin / ry,
                    sin / rx, cos / ry,
                    -(cos * cx + sin * cy) / rx, (sin * cx - cos * cy) / ry);
            },

            /**
             * Uses the given path commands to begin a new path on the canvas.
             * @ignore
             */
            appendPath: function(path) {
                var me = this,
                    i = 0,
                    j = 0,
                    commands = path.commands,
                    params = path.params,
                    ln = commands.length;

                me.beginPath();

                for (; i < ln; i++) {
                    switch (commands[i]) {
                        case 'M':
                            me.moveTo(params[j], params[j + 1]);
                            j += 2;
                            break;

                        case 'L':
                            me.lineTo(params[j], params[j + 1]);
                            j += 2;
                            break;

                        case 'C':
                            me.bezierCurveTo(
                                params[j], params[j + 1],
                                params[j + 2], params[j + 3],
                                params[j + 4], params[j + 5]
                            );
                            j += 6;
                            break;

                        case 'Z':
                            me.closePath();
                            break;
                    }
                }
            },

            save: function() {
                var toSave = this.toSave,
                    ln = toSave.length,
                    obj = ln && {}, // Don't allocate memory if we don't have to.
                    i = 0,
                    key;

                for (; i < ln; i++) {
                    key = toSave[i];

                    if (key in this) {
                        obj[key] = this[key];
                    }
                }

                this.state.push(obj);
                this.$save();
            },

            restore: function() {
                var obj = this.state.pop(),
                    key;

                if (obj) {
                    for (key in obj) {
                        this[key] = obj[key];
                    }
                }

                this.$restore();
            }
        }
    },

    splitThreshold: 3000,

    /**
     * @private
     * Properties to be saved/restored in the `save` and `restore` methods.
     */
    toSave: ['fillGradient', 'strokeGradient'],

    /**
     * @property element
     * @inheritdoc
     */
    element: {
        reference: 'element',
        children: [{
            reference: 'bodyElement',
            style: {
                width: '100%',
                height: '100%',
                position: 'relative'
            }
        }]
    },

    /**
     * @private
     *
     * Creates the canvas element.
     */
    createCanvas: function() {
        var canvas, overrides, ctx, name;

        canvas = Ext.Element.create({
            tag: 'canvas',
            cls: Ext.baseCSSPrefix + 'surface-canvas'
        });

        // Emulate Canvas in IE8 with VML.
        if (window.G_vmlCanvasManager) {
            G_vmlCanvasManager.initElement(canvas.dom);
            this.isVML = true;
        }

        overrides = Ext.draw.engine.Canvas.contextOverrides;
        ctx = canvas.dom.getContext('2d');

        if (ctx.ellipse) {
            delete overrides.ellipse;
        }

        ctx.state = [];
        ctx.toSave = this.toSave;

        // Saving references to the native Canvas context methods that we'll be overriding.
        for (name in overrides) {
            ctx['$' + name] = ctx[name];
        }

        Ext.apply(ctx, overrides);

        if (this.getHighPrecision()) {
            this.enablePrecisionCompensation(ctx);
        }
        else {
            this.disablePrecisionCompensation(ctx);
        }

        this.bodyElement.appendChild(canvas);
        this.canvases.push(canvas);
        this.contexts.push(ctx);
    },

    updateHighPrecision: function(highPrecision) {
        var contexts = this.contexts,
            ln = contexts.length,
            i, context;

        for (i = 0; i < ln; i++) {
            context = contexts[i];

            if (highPrecision) {
                this.enablePrecisionCompensation(context);
            }
            else {
                this.disablePrecisionCompensation(context);
            }
        }
    },

    precisionNames: [
        'rect',
        'fillRect',
        'strokeRect',
        'clearRect',
        'moveTo',
        'lineTo',
        'arc',
        'arcTo',
        'save',
        'restore',
        'updatePrecisionCompensate',
        'setTransform',
        'transform',
        'scale',
        'translate',
        'rotate',
        'quadraticCurveTo',
        'bezierCurveTo',
        'createLinearGradient',
        'createRadialGradient',
        'fillText',
        'strokeText',
        'drawImage'
    ],

    /**
     * @private
     * Clears canvas of compensation for canvas' use of single precision floating point.
     * @param {CanvasRenderingContext2D} ctx The canvas context.
     */
    disablePrecisionCompensation: function(ctx) {
        var regularOverrides = Ext.draw.engine.Canvas.contextOverrides,
            precisionOverrides = this.precisionNames,
            ln = precisionOverrides.length,
            i, name;

        for (i = 0; i < ln; i++) {
            name = precisionOverrides[i];

            if (!(name in regularOverrides)) {
                delete ctx[name];
            }
        }

        this.setDirty(true);
    },

    /**
     * @private
     * Compensate for canvas' use of single precision floating point.
     * @param {CanvasRenderingContext2D} ctx The canvas context.
     */
    enablePrecisionCompensation: function(ctx) {
        var surface = this,
            xx = 1,
            yy = 1,
            dx = 0,
            dy = 0,
            matrix = new Ext.draw.Matrix(),
            transStack = [],
            comp = {},
            regularOverrides = Ext.draw.engine.Canvas.contextOverrides,
            originalCtx = ctx.constructor.prototype,

            /**
             * @cfg {Object} precisionOverrides
             * @ignore
             */
            precisionOverrides = {
                toSave: surface.toSave,
                /**
                 * Adds a new closed subpath to the path, representing the given rectangle.
                 * @return {*}
                 * @ignore
                 */
                rect: function(x, y, w, h) {
                    return originalCtx.rect.call(this, x * xx + dx, y * yy + dy, w * xx, h * yy);
                },

                /**
                 * Paints the given rectangle onto the canvas, using the current fill style.
                 * @ignore
                 */
                fillRect: function(x, y, w, h) {
                    this.updatePrecisionCompensateRect();
                    originalCtx.fillRect.call(this, x * xx + dx, y * yy + dy, w * xx, h * yy);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Paints the box that outlines the given rectangle onto the canvas, using
                 * the current stroke style.
                 * @ignore
                 */
                strokeRect: function(x, y, w, h) {
                    this.updatePrecisionCompensateRect();
                    originalCtx.strokeRect.call(this, x * xx + dx, y * yy + dy, w * xx, h * yy);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Clears all pixels on the canvas in the given rectangle to transparent black.
                 * @ignore
                 */
                clearRect: function(x, y, w, h) {
                    return originalCtx.clearRect.call(this, x * xx + dx, y * yy + dy, w * xx,
                                                      h * yy);
                },

                /**
                 * Creates a new subpath with the given point.
                 * @ignore
                 */
                moveTo: function(x, y) {
                    return originalCtx.moveTo.call(this, x * xx + dx, y * yy + dy);
                },

                /**
                 * Adds the given point to the current subpath, connected to the previous one
                 * by a straight line.
                 * @ignore
                 */
                lineTo: function(x, y) {
                    return originalCtx.lineTo.call(this, x * xx + dx, y * yy + dy);
                },

                /**
                 * Adds points to the subpath such that the arc described by the circumference
                 * of the circle described by the arguments, starting at the given start angle
                 * and ending at the given end angle, going in the given direction (defaulting
                 * to clockwise), is added to the path, connected to the previous point
                 * by a straight line.
                 * @ignore
                 */
                arc: function(x, y, radius, startAngle, endAngle, anticlockwise) {
                    this.updatePrecisionCompensateRect();
                    originalCtx.arc.call(this, x * xx + dx, y * xx + dy, radius * xx, startAngle,
                                         endAngle, anticlockwise);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Adds an arc with the given control points and radius to the current subpath,
                 * connected to the previous point by a straight line.  If two radii are provided,
                 * the first controls the width of the arc's ellipse, and the second controls
                 * the height. If only one is provided, or if they are the same, the arc is from
                 * a circle.
                 *
                 * In the case of an ellipse, the rotation argument controls the clockwise
                 * inclination of the ellipse relative to the x-axis.
                 * @ignore
                 */
                arcTo: function(x1, y1, x2, y2, radius) {
                    this.updatePrecisionCompensateRect();
                    originalCtx.arcTo.call(this, x1 * xx + dx, y1 * yy + dy, x2 * xx + dx,
                                           y2 * yy + dy, radius * xx);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Pushes the context state to the state stack.
                 * @ignore
                 */
                save: function() {
                    transStack.push(matrix);
                    matrix = matrix.clone();
                    regularOverrides.save.call(this);
                    originalCtx.save.call(this);
                },

                /**
                 * Pops the state stack and restores the state.
                 * @ignore
                 */
                restore: function() {
                    matrix = transStack.pop();
                    regularOverrides.restore.call(this);
                    originalCtx.restore.call(this);
                    this.updatePrecisionCompensate();
                },

                /**
                 * @ignore
                 */
                updatePrecisionCompensate: function() {
                    matrix.precisionCompensate(surface.devicePixelRatio, comp);
                    xx = comp.xx;
                    yy = comp.yy;
                    dx = comp.dx;
                    dy = comp.dy;
                    originalCtx.setTransform.call(this, surface.devicePixelRatio, comp.b, comp.c,
                                                  comp.d, 0, 0);
                },

                /**
                 * @ignore
                 */
                updatePrecisionCompensateRect: function() {
                    matrix.precisionCompensateRect(surface.devicePixelRatio, comp);
                    xx = comp.xx;
                    yy = comp.yy;
                    dx = comp.dx;
                    dy = comp.dy;
                    originalCtx.setTransform.call(this, surface.devicePixelRatio, comp.b, comp.c,
                                                  comp.d, 0, 0);
                },

                /**
                 * Changes the transformation matrix to the matrix given by the arguments
                 * as described below.
                 * @ignore
                 */
                setTransform: function(x2x, x2y, y2x, y2y, newDx, newDy) {
                    matrix.set(x2x, x2y, y2x, y2y, newDx, newDy);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Changes the transformation matrix to apply the matrix given by the arguments
                 * as described below.
                 * @ignore
                 */
                transform: function(x2x, x2y, y2x, y2y, newDx, newDy) {
                    matrix.append(x2x, x2y, y2x, y2y, newDx, newDy);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Scales the transformation matrix.
                 * @return {*}
                 * @ignore
                 */
                scale: function(sx, sy) {
                    this.transform(sx, 0, 0, sy, 0, 0);
                },

                /**
                 * Translates the transformation matrix.
                 * @return {*}
                 * @ignore
                 */
                translate: function(dx, dy) {
                    this.transform(1, 0, 0, 1, dx, dy);
                },

                /**
                 * Rotates the transformation matrix.
                 * @return {*}
                 * @ignore
                 */
                rotate: function(radians) {
                    var cos = Math.cos(radians),
                        sin = Math.sin(radians);

                    this.transform(cos, sin, -sin, cos, 0, 0);
                },

                /**
                 * Adds the given point to the current subpath, connected to the previous one by a
                 * quadratic Bézier curve with the given control point.
                 * @return {*}
                 * @ignore
                 */
                quadraticCurveTo: function(cx, cy, x, y) {
                    originalCtx.quadraticCurveTo.call(this, cx * xx + dx, cy * yy + dy,
                                                      x * xx + dx, y * yy + dy);
                },

                /**
                 * Adds the given point to the current subpath, connected to the previous one
                 * by a cubic Bézier curve with the given control points.
                 * @return {*}
                 * @ignore
                 */
                bezierCurveTo: function(c1x, c1y, c2x, c2y, x, y) {
                    originalCtx.bezierCurveTo.call(this, c1x * xx + dx, c1y * yy + dy,
                                                   c2x * xx + dx, c2y * yy + dy,
                                                   x * xx + dx, y * yy + dy);
                },

                /**
                 * Returns an object that represents a linear gradient that paints along the line
                 * given by the coordinates represented by the arguments.
                 * @return {*}
                 * @ignore
                 */
                createLinearGradient: function(x0, y0, x1, y1) {
                    var grad;

                    this.updatePrecisionCompensateRect();
                    grad = originalCtx.createLinearGradient.call(this, x0 * xx + dx, y0 * yy + dy,
                                                                 x1 * xx + dx, y1 * yy + dy);

                    this.updatePrecisionCompensate();

                    return grad;
                },

                /**
                 * Returns a CanvasGradient object that represents a radial gradient that paints
                 * along the cone given by the circles represented by the arguments. If either
                 * of the radii are negative, throws an IndexSizeError exception.
                 * @return {*}
                 * @ignore
                 */
                createRadialGradient: function(x0, y0, r0, x1, y1, r1) {
                    var grad;

                    this.updatePrecisionCompensateRect();
                    grad = originalCtx.createLinearGradient.call(this, x0 * xx + dx, y0 * xx + dy,
                                                                 r0 * xx, x1 * xx + dx,
                                                                 y1 * xx + dy, r1 * xx);

                    this.updatePrecisionCompensate();

                    return grad;
                },

                /**
                 * Fills the given text at the given position. If a maximum width is provided,
                 * the text will be scaled to fit that width if necessary.
                 * @ignore
                 */
                fillText: function(text, x, y, maxWidth) {
                    originalCtx.setTransform.apply(this, matrix.elements);

                    if (typeof maxWidth === 'undefined') {
                        originalCtx.fillText.call(this, text, x, y);
                    }
                    else {
                        originalCtx.fillText.call(this, text, x, y, maxWidth);
                    }

                    this.updatePrecisionCompensate();
                },

                /**
                 * Strokes the given text at the given position. If a
                 * maximum width is provided, the text will be scaled to
                 * fit that width if necessary.
                 * @ignore
                 */
                strokeText: function(text, x, y, maxWidth) {
                    originalCtx.setTransform.apply(this, matrix.elements);

                    if (typeof maxWidth === 'undefined') {
                        originalCtx.strokeText.call(this, text, x, y);
                    }
                    else {
                        originalCtx.strokeText.call(this, text, x, y, maxWidth);
                    }

                    this.updatePrecisionCompensate();
                },

                /**
                 * Fills the subpaths of the current default path or the given path with the current
                 * fill style.
                 * @ignore
                 */
                fill: function() {
                    var fillGradient = this.fillGradient,
                        bbox = this.bbox;

                    this.updatePrecisionCompensateRect();

                    if (fillGradient && bbox) {
                        this.fillStyle = fillGradient.generateGradient(this, bbox);
                    }

                    originalCtx.fill.call(this);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Strokes the subpaths of the current default path or the given path with the
                 * current stroke style.
                 * @ignore
                 */
                stroke: function() {
                    var strokeGradient = this.strokeGradient,
                        bbox = this.bbox;

                    this.updatePrecisionCompensateRect();

                    if (strokeGradient && bbox) {
                        this.strokeStyle = strokeGradient.generateGradient(this, bbox);
                    }

                    originalCtx.stroke.call(this);
                    this.updatePrecisionCompensate();
                },

                /**
                 * Draws the given image onto the canvas.  If the first argument isn't an img,
                 * canvas, or video element, throws a TypeMismatchError exception. If the image
                 * has no image data, throws an InvalidStateError exception. If the one of the
                 * source rectangle dimensions is zero, throws an IndexSizeError exception.
                 * If the image isn't yet fully decoded, then nothing is drawn.
                 * @return {*}
                 * @ignore
                 */
                drawImage: function(img_elem, arg1, arg2, arg3, arg4, dst_x, dst_y, dw, dh) {
                    switch (arguments.length) {
                        case 3:
                            return originalCtx.drawImage.call(this, img_elem, arg1 * xx + dx,
                                                              arg2 * yy + dy);

                        case 5:
                            return originalCtx.drawImage.call(this, img_elem, arg1 * xx + dx,
                                                              arg2 * yy + dy, arg3 * xx, arg4 * yy);

                        case 9:
                            return originalCtx.drawImage.call(this, img_elem, arg1, arg2, arg3,
                                                              arg4, dst_x * xx + dx,
                                                              dst_y * yy * dy, dw * xx, dh * yy);
                    }
                }
            };

        Ext.apply(ctx, precisionOverrides);
        this.setDirty(true);
    },

    /**
     * Normally, a surface will have a single canvas.
     * However, on certain platforms/browsers there's a limit to how big a canvas can be.
     * 'splitThreshold' is used to determine maximum width/height of a single canvas element.
     * When a surface is wider/taller than the splitThreshold, extra canvas element(s)
     * will be created and tiled inside the surface.
     */
    updateRect: function(rect) {
        this.callParent([rect]);

        // eslint-disable-next-line vars-on-top
        var me = this,
            l = Math.floor(rect[0]),
            t = Math.floor(rect[1]),
            r = Math.ceil(rect[0] + rect[2]),
            b = Math.ceil(rect[1] + rect[3]),
            devicePixelRatio = me.devicePixelRatio,
            canvases = me.canvases,
            w = r - l,
            h = b - t,
            splitThreshold = Math.round(me.splitThreshold / devicePixelRatio),
            xSplits = me.xSplits = Math.ceil(w / splitThreshold),
            ySplits = me.ySplits = Math.ceil(h / splitThreshold),
            i, j, k, offsetX, offsetY,
            dom, width, height;

        for (j = 0, offsetY = 0; j < ySplits; j++, offsetY += splitThreshold) {

            for (i = 0, offsetX = 0; i < xSplits; i++, offsetX += splitThreshold) {

                k = j * xSplits + i;

                if (k >= canvases.length) {
                    me.createCanvas();
                }

                dom = canvases[k].dom;

                dom.style.left = offsetX + 'px';
                dom.style.top = offsetY + 'px';

                // The Canvas doesn't automatically support hi-DPI displays.
                // We have to actually create a larger canvas (more pixels)
                // while keeping its physical size the same.

                height = Math.min(splitThreshold, h - offsetY);

                if (height * devicePixelRatio !== dom.height) {
                    dom.height = height * devicePixelRatio;
                    dom.style.height = height + 'px';
                }

                width = Math.min(splitThreshold, w - offsetX);

                if (width * devicePixelRatio !== dom.width) {
                    dom.width = width * devicePixelRatio;
                    dom.style.width = width + 'px';
                }

                me.applyDefaults(me.contexts[k]);

            }

        }

        me.activeCanvases = k = xSplits * ySplits;

        while (canvases.length > k) {
            canvases.pop().destroy();
        }

        me.clear();
    },

    /**
     * @method clearTransform
     * @inheritdoc
     */
    clearTransform: function() {
        var me = this,
            xSplits = me.xSplits,
            ySplits = me.ySplits,
            contexts = me.contexts,
            splitThreshold = me.splitThreshold,
            devicePixelRatio = me.devicePixelRatio,
            i, j, k, ctx;

        for (i = 0; i < xSplits; i++) {

            for (j = 0; j < ySplits; j++) {

                k = j * xSplits + i;

                ctx = contexts[k];

                ctx.translate(
                    -splitThreshold * i,
                    -splitThreshold * j
                );
                ctx.scale(
                    devicePixelRatio,
                    devicePixelRatio
                );
                me.matrix.toContext(ctx);

            }

        }
    },

    /**
     * @method renderSprite
     * @inheritdoc
     */
    renderSprite: function(sprite) {
        var me = this,
            rect = me.getRect(),
            surfaceMatrix = me.matrix,
            parent = sprite.getParent(),
            matrix = Ext.draw.Matrix.fly([1, 0, 0, 1, 0, 0]),
            splitThreshold = me.splitThreshold / me.devicePixelRatio,
            xSplits = me.xSplits,
            ySplits = me.ySplits,
            offsetX, offsetY,
            ctx, bbox, width, height,
            left = 0,
            right,
            top = 0,
            bottom,
            w = rect[2],
            h = rect[3],
            i, j, k;

        while (parent && parent.isSprite) {
            matrix.prependMatrix(parent.matrix || parent.attr && parent.attr.matrix);
            parent = parent.getParent();
        }

        matrix.prependMatrix(surfaceMatrix);

        bbox = sprite.getBBox();

        if (bbox) {
            bbox = matrix.transformBBox(bbox);
        }

        sprite.preRender(me);

        if (sprite.attr.hidden || sprite.attr.globalAlpha === 0) {
            sprite.setDirty(false);

            return;
        }

        // Render this sprite on all Canvas elements it spans, skipping the rest.
        for (j = 0, offsetY = 0; j < ySplits; j++, offsetY += splitThreshold) {

            for (i = 0, offsetX = 0; i < xSplits; i++, offsetX += splitThreshold) {

                k = j * xSplits + i;

                ctx = me.contexts[k];

                width = Math.min(splitThreshold, w - offsetX);
                height = Math.min(splitThreshold, h - offsetY);

                left = offsetX;
                right = left + width;

                top = offsetY;
                bottom = top + height;

                if (bbox) {
                    if (bbox.x > right ||
                        bbox.x + bbox.width < left ||
                        bbox.y > bottom ||
                        bbox.y + bbox.height < top) {
                        continue;
                    }
                }

                ctx.save();
                sprite.useAttributes(ctx, rect);

                if (false === sprite.render(me, ctx, [left, top, width, height])) {
                    return false;
                }

                ctx.restore();
            }

        }

        sprite.setDirty(false);
    },

    flatten: function(size, surfaces) {
        var targetCanvas = document.createElement('canvas'),
            className = Ext.getClassName(this),
            ratio = this.devicePixelRatio,
            ctx = targetCanvas.getContext('2d'),
            surface, canvas, rect, i, j, xy;

        targetCanvas.width = Math.ceil(size.width * ratio);
        targetCanvas.height = Math.ceil(size.height * ratio);

        for (i = 0; i < surfaces.length; i++) {
            surface = surfaces[i];

            if (Ext.getClassName(surface) !== className) {
                continue;
            }

            rect = surface.getRect();

            for (j = 0; j < surface.canvases.length; j++) {
                canvas = surface.canvases[j];
                xy = canvas.getOffsetsTo(canvas.getParent());
                ctx.drawImage(canvas.dom, (rect[0] + xy[0]) * ratio, (rect[1] + xy[1]) * ratio);
            }
        }

        return {
            data: targetCanvas.toDataURL(),
            type: 'png'
        };
    },

    applyDefaults: function(ctx) {
        var none = Ext.util.Color.RGBA_NONE;

        ctx.strokeStyle = none;
        ctx.fillStyle = none;
        ctx.textAlign = 'start';
        ctx.textBaseline = 'alphabetic';
        ctx.miterLimit = 1;
    },

    /**
     * @method clear
     * @inheritdoc
     */
    clear: function() {
        var me = this,
            activeCanvases = me.activeCanvases,
            i, canvas, ctx;

        for (i = 0; i < activeCanvases; i++) {
            canvas = me.canvases[i].dom;
            ctx = me.contexts[i];
            ctx.setTransform(1, 0, 0, 1, 0, 0);
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }

        me.setDirty(true);
    },

    /**
     * Destroys the Canvas element and prepares it for Garbage Collection.
     */
    destroy: function() {
        var me = this,
            canvases = me.canvases,
            ln = canvases.length,
            i;

        for (i = 0; i < ln; i++) {
            me.contexts[i] = null;
            canvases[i].destroy();
            canvases[i] = null;
        }

        me.contexts = me.canvases = null;

        me.callParent();
    },

    privates: {
        initElement: function() {
            var me = this;

            me.callParent();
            me.canvases = [];
            me.contexts = [];
            me.activeCanvases = me.xSplits = me.ySplits = 0;
        }
    }
}, function() {
    var me = this,
        proto = me.prototype,
        splitThreshold = 1e10;

    if (Ext.os.is.Android4 && Ext.browser.is.Chrome) {
        splitThreshold = 3000;
    }
    else if (Ext.is.iOS) {
        splitThreshold = 2200;
    }

    proto.splitThreshold = splitThreshold;
});
