/**
 * @class Ext.chart.series.sprite.Pie3DPart
 * @extends Ext.draw.sprite.Path
 *
 * Pie3D series sprite.
 */
Ext.define('Ext.chart.series.sprite.Pie3DPart', {
    extend: 'Ext.draw.sprite.Path',
    mixins: {
        markerHolder: 'Ext.chart.MarkerHolder'
    },
    alias: 'sprite.pie3dPart',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [centerX=0]
                 * The central point of the series on the x-axis.
                 */
                centerX: 'number',

                /**
                 * @cfg {Number} [centerY=0]
                 * The central point of the series on the x-axis.
                 */
                centerY: 'number',

                /**
                 * @cfg {Number} [startAngle=0]
                 * The starting angle of the polar series.
                 */
                startAngle: 'number',

                /**
                 * @cfg {Number} [endAngle=Math.PI]
                 * The ending angle of the polar series.
                 */
                endAngle: 'number',

                /**
                 * @cfg {Number} [startRho=0]
                 * The starting radius of the polar series.
                 */
                startRho: 'number',

                /**
                 * @cfg {Number} [endRho=150]
                 * The ending radius of the polar series.
                 */
                endRho: 'number',

                /**
                 * @cfg {Number} [margin=0]
                 * Margin from the center of the pie. Used for donut.
                 */
                margin: 'number',

                /**
                 * @cfg {Number} [thickness=0]
                 * The thickness of the 3D pie part.
                 */
                thickness: 'number',

                /**
                 * @cfg {Number} [bevelWidth=5]
                 * The size of the 3D pie bevel.
                 */
                bevelWidth: 'number',

                /**
                 * @cfg {Number} [distortion=0]
                 * The distortion of the 3D pie part.
                 */
                distortion: 'number',

                /**
                 * @cfg {Object} [baseColor='white']
                 * The color of the 3D pie part before adding the 3D effect.
                 */
                baseColor: 'color',

                /**
                 * @cfg {Number} [colorSpread=0.7]
                 * An attribute used to control how flat the gradient of the sprite looks.
                 * A value of 0 essentially means no gradient (flat color).
                 */
                colorSpread: 'number',

                /**
                 * @cfg {Number} [baseRotation=0]
                 * The starting rotation of the polar series.
                 */
                baseRotation: 'number',

                /**
                 * @cfg {String} [part='top']
                 * The part of the 3D Pie represented by the sprite.
                 */
                part: 'enums(top,bottom,start,end,innerFront,innerBack,outerFront,outerBack)',

                /**
                 * @cfg {String} [label='']
                 * The label associated with the 'top' part of the sprite.
                 */
                label: 'string'
            },
            aliases: {
                rho: 'endRho'
            },
            triggers: {
                centerX: 'path,bbox',
                centerY: 'path,bbox',
                startAngle: 'path,partZIndex',
                endAngle: 'path,partZIndex',
                startRho: 'path',
                endRho: 'path,bbox',
                margin: 'path,bbox',
                thickness: 'path',
                distortion: 'path',
                baseRotation: 'path,partZIndex',
                baseColor: 'partZIndex,partColor',
                colorSpread: 'partColor',
                part: 'path,partZIndex',
                globalAlpha: 'canvas,alpha',
                fillOpacity: 'canvas,alpha'
            },
            defaults: {
                centerX: 0,
                centerY: 0,
                startAngle: Math.PI * 2,
                endAngle: Math.PI * 2,
                startRho: 0,
                endRho: 150,
                margin: 0,
                thickness: 35,
                distortion: 0.5,
                baseRotation: 0,
                baseColor: 'white',
                colorSpread: 0.5,
                miterLimit: 1,
                bevelWidth: 5,
                strokeOpacity: 0,
                part: 'top',
                label: ''
            },
            updaters: {
                alpha: 'alphaUpdater',
                partColor: 'partColorUpdater',
                partZIndex: 'partZIndexUpdater'
            }
        }
    },

    config: {
        renderer: null,
        rendererData: null,
        rendererIndex: 0,
        series: null
    },

    bevelParams: [],

    constructor: function(config) {
        this.callParent([config]);

        this.bevelGradient = new Ext.draw.gradient.Linear({
            stops: [{
                offset: 0,
                color: 'rgba(255,255,255,0)'
            }, {
                offset: 0.7,
                color: 'rgba(255,255,255,0.6)'
            }, {
                offset: 1,
                color: 'rgba(255,255,255,0)'
            }]
        });
    },

    updateRenderer: function() {
        this.setDirty(true);
    },

    updateRendererData: function() {
        this.setDirty(true);
    },

    updateRendererIndex: function() {
        this.setDirty(true);
    },

    alphaUpdater: function(attr) {
        var me = this,
            opacity = attr.globalAlpha,
            fillOpacity = attr.fillOpacity,
            oldOpacity = me.oldOpacity,
            oldFillOpacity = me.oldFillOpacity;

        // Update the path when the sprite becomes translucent or completely opaque.
        if ((opacity !== oldOpacity && (opacity === 1 || oldOpacity === 1)) ||
            (fillOpacity !== oldFillOpacity && (fillOpacity === 1 || oldFillOpacity === 1))) {
            me.scheduleUpdater(attr, 'path', ['globalAlpha']);
            me.oldOpacity = opacity;
            me.oldFillOpacity = fillOpacity;
        }
    },

    partColorUpdater: function(attr) {
        var color = Ext.util.Color.fly(attr.baseColor),
            colorString = color.toString(),
            colorSpread = attr.colorSpread,
            fillStyle;

        switch (attr.part) {
            case 'top':
                fillStyle = new Ext.draw.gradient.Radial({
                    start: {
                        x: 0,
                        y: 0,
                        r: 0
                    },
                    end: {
                        x: 0,
                        y: 0,
                        r: 1
                    },
                    stops: [{
                        offset: 0,
                        color: color.createLighter(0.1 * colorSpread)
                    }, {
                        offset: 1,
                        color: color.createDarker(0.1 * colorSpread)
                    }]
                });

                break;

            case 'bottom':
                fillStyle = new Ext.draw.gradient.Radial({
                    start: {
                        x: 0,
                        y: 0,
                        r: 0
                    },
                    end: {
                        x: 0,
                        y: 0,
                        r: 1
                    },
                    stops: [{
                        offset: 0,
                        color: color.createDarker(0.2 * colorSpread)
                    }, {
                        offset: 1,
                        color: color.toString()
                    }]
                });

                break;

            case 'outerFront':
            case 'outerBack':
                fillStyle = new Ext.draw.gradient.Linear({
                    stops: [{
                        offset: 0,
                        color: color.createDarker(0.15 * colorSpread).toString()
                    }, {
                        offset: 0.3,
                        color: colorString
                    }, {
                        offset: 0.8,
                        color: color.createLighter(0.2 * colorSpread).toString()
                    }, {
                        offset: 1,
                        color: color.createDarker(0.25 * colorSpread).toString()
                    }]
                });

                break;

            case 'start':
                fillStyle = new Ext.draw.gradient.Linear({
                    stops: [{
                        offset: 0,
                        color: color.createDarker(0.1 * colorSpread).toString()
                    }, {
                        offset: 1,
                        color: color.createLighter(0.2 * colorSpread).toString()
                    }]
                });

                break;

            case 'end':
                fillStyle = new Ext.draw.gradient.Linear({
                    stops: [{
                        offset: 0,
                        color: color.createDarker(0.1 * colorSpread).toString()
                    }, {
                        offset: 1,
                        color: color.createLighter(0.2 * colorSpread).toString()
                    }]
                });

                break;

            case 'innerFront':
            case 'innerBack':
                fillStyle = new Ext.draw.gradient.Linear({
                    stops: [{
                        offset: 0,
                        color: color.createDarker(0.1 * colorSpread).toString()
                    }, {
                        offset: 0.2,
                        color: color.createLighter(0.2 * colorSpread).toString()
                    }, {
                        offset: 0.7,
                        color: colorString
                    }, {
                        offset: 1,
                        color: color.createDarker(0.1 * colorSpread).toString()
                    }]
                });

                break;
        }

        attr.fillStyle = fillStyle;
        attr.canvasAttributes.fillStyle = fillStyle;
    },

    partZIndexUpdater: function(attr) {
        var normalize = Ext.draw.sprite.AttributeParser.angle,
            rotation = attr.baseRotation,
            startAngle = attr.startAngle,
            endAngle = attr.endAngle,
            depth;

        switch (attr.part) {
            case 'top':
                attr.zIndex = 6;
                break;

            case 'outerFront':
                startAngle = normalize(startAngle + rotation);
                endAngle = normalize(endAngle + rotation);

                if (startAngle >= 0 && endAngle < 0) {
                    depth = Math.sin(startAngle);
                }
                else if (startAngle <= 0 && endAngle > 0) {
                    depth = Math.sin(endAngle);
                }
                else if (startAngle >= 0 && endAngle > 0) {
                    if (startAngle > endAngle) {
                        depth = 0;
                    }
                    else {
                        depth = Math.max(Math.sin(startAngle), Math.sin(endAngle));
                    }
                }
                else {
                    depth = 1;
                }

                attr.zIndex = 4 + depth;
                break;

            case 'outerBack':
                attr.zIndex = 1;
                break;

            case 'start':
                attr.zIndex = 4 + Math.sin(normalize(startAngle + rotation));
                break;

            case 'end':
                attr.zIndex = 4 + Math.sin(normalize(endAngle + rotation));
                break;

            case 'innerFront':
                attr.zIndex = 2;
                break;

            case 'innerBack':
                attr.zIndex = 4 + Math.sin(normalize((startAngle + endAngle) / 2 + rotation));
                break;

            case 'bottom':
                attr.zIndex = 0;
                break;
        }

        attr.dirtyZIndex = true;
    },

    updatePlainBBox: function(plain) {
        var attr = this.attr,
            part = attr.part,
            baseRotation = attr.baseRotation,
            centerX = attr.centerX,
            centerY = attr.centerY,
            rho, angle, x, y, sin, cos;

        if (part === 'start') {
            angle = attr.startAngle + baseRotation;
        }
        else if (part === 'end') {
            angle = attr.endAngle + baseRotation;
        }

        if (Ext.isNumber(angle)) {
            sin = Math.sin(angle);
            cos = Math.cos(angle);

            x = Math.min(
                centerX + cos * attr.startRho,
                centerX + cos * attr.endRho
            );
            y = centerY + sin * attr.startRho * attr.distortion;

            plain.x = x;
            plain.y = y;
            plain.width = cos * (attr.endRho - attr.startRho);
            plain.height = attr.thickness + sin * (attr.endRho - attr.startRho) * 2;

            return;
        }

        if (part === 'innerFront' || part === 'innerBack') {
            rho = attr.startRho;
        }
        else {
            rho = attr.endRho;
        }

        plain.width = rho * 2;
        plain.height = rho * attr.distortion * 2 + attr.thickness;
        plain.x = attr.centerX - rho;
        plain.y = attr.centerY - rho * attr.distortion;
    },

    updateTransformedBBox: function(transform) {
        if (this.attr.part === 'start' || this.attr.part === 'end') {
            return this.callParent(arguments);
        }

        return this.updatePlainBBox(transform);
    },

    updatePath: function(path) {
        if (!this.attr.globalAlpha) {
            return;
        }

        if (this.attr.endAngle < this.attr.startAngle) {
            return;
        }

        this[this.attr.part + 'Renderer'](path);
    },

    render: function(surface, ctx, rect) {
        var me = this,
            renderer = me.getRenderer(),
            attr = me.attr,
            part = attr.part,
            itemCfg, changes;

        if (!attr.globalAlpha || Ext.Number.isEqual(attr.startAngle, attr.endAngle, 1e-8)) {
            return;
        }

        if (renderer) {
            itemCfg = {
                type: 'pie3dPart',
                part: attr.part,
                margin: attr.margin,
                distortion: attr.distortion,
                centerX: attr.centerX,
                centerY: attr.centerY,
                baseRotation: attr.baseRotation,
                startAngle: attr.startAngle,
                endAngle: attr.endAngle,
                startRho: attr.startRho,
                endRho: attr.endRho
            };

            changes = Ext.callback(renderer, null,
                                   [me, itemCfg, me.getRendererData(), me.getRendererIndex()],
                                   0, me.getSeries());

            if (changes) {
                if (changes.part) {
                    // Can't let users change the nature of the sprite.
                    changes.part = part;
                }

                me.setAttributes(changes);
                me.useAttributes(ctx, rect);
            }
        }

        me.callParent([surface, ctx]);
        me.bevelRenderer(surface, ctx);

        // Only the top part will have the label attribute (set by the series).
        if (attr.label && me.getMarker('labels')) {
            me.placeLabel();
        }
    },

    placeLabel: function() {
        var me = this,
            attr = me.attr,
            attributeId = attr.attributeId,
            margin = attr.margin,
            distortion = attr.distortion,
            centerX = attr.centerX,
            centerY = attr.centerY,
            baseRotation = attr.baseRotation,
            startAngle = attr.startAngle + baseRotation,
            endAngle = attr.endAngle + baseRotation,
            midAngle = (startAngle + endAngle) / 2,
            startRho = attr.startRho + margin,
            endRho = attr.endRho + margin,
            midRho = (startRho + endRho) / 2,
            sin = Math.sin(midAngle),
            cos = Math.cos(midAngle),
            surfaceMatrix = me.surfaceMatrix,
            label = me.getMarker('labels'),
            labelTpl = label.getTemplate(),
            calloutLine = labelTpl.getCalloutLine(),
            calloutLineLength = calloutLine && calloutLine.length || 40,
            labelCfg = {},
            rendererParams, rendererChanges,
            x, y;

        surfaceMatrix.appendMatrix(attr.matrix);

        labelCfg.text = attr.label;

        x = centerX + cos * midRho;
        y = centerY + sin * midRho * distortion;

        labelCfg.x = surfaceMatrix.x(x, y);
        labelCfg.y = surfaceMatrix.y(x, y);

        x = centerX + cos * endRho;
        y = centerY + sin * endRho * distortion;

        labelCfg.calloutStartX = surfaceMatrix.x(x, y);
        labelCfg.calloutStartY = surfaceMatrix.y(x, y);

        x = centerX + cos * (endRho + calloutLineLength);
        y = centerY + sin * (endRho + calloutLineLength) * distortion;
        labelCfg.calloutPlaceX = surfaceMatrix.x(x, y);
        labelCfg.calloutPlaceY = surfaceMatrix.y(x, y);

        labelCfg.calloutWidth = 2;

        if (labelTpl.attr.renderer) {
            rendererParams = [me.attr.label, label, labelCfg, me.getRendererData(),
                              me.getRendererIndex()];

            rendererChanges = Ext.callback(labelTpl.attr.renderer, null, rendererParams,
                                           0, me.getSeries());

            if (typeof rendererChanges === 'string') {
                labelCfg.text = rendererChanges;
            }
            else {
                Ext.apply(labelCfg, rendererChanges);
            }
        }

        me.putMarker('labels', labelCfg, attributeId);

        me.putMarker('labels', {
            callout: 1
        }, attributeId);
    },

    bevelRenderer: function(surface, ctx) {
        var me = this,
            attr = me.attr,
            bevelWidth = attr.bevelWidth,
            params = me.bevelParams,
            i;

        for (i = 0; i < params.length; i++) {
            ctx.beginPath();
            ctx.ellipse.apply(ctx, params[i]);
            ctx.save();
            ctx.lineWidth = bevelWidth;
            ctx.strokeOpacity = bevelWidth ? 1 : 0;
            ctx.strokeGradient = me.bevelGradient;
            ctx.stroke(attr);
            ctx.restore();
        }
    },

    lidRenderer: function(path, thickness) {
        var attr = this.attr,
            margin = attr.margin,
            distortion = attr.distortion,
            centerX = attr.centerX,
            centerY = attr.centerY,
            baseRotation = attr.baseRotation,
            startAngle = attr.startAngle + baseRotation,
            endAngle = attr.endAngle + baseRotation,
            midAngle = (startAngle + endAngle) / 2,
            startRho = attr.startRho,
            endRho = attr.endRho,
            sinEnd = Math.sin(endAngle),
            cosEnd = Math.cos(endAngle);

        centerX += Math.cos(midAngle) * margin;
        centerY += Math.sin(midAngle) * margin * distortion;

        path.ellipse(
            centerX, centerY + thickness,
            startRho, startRho * distortion,
            0, startAngle, endAngle, false
        );
        path.lineTo(
            centerX + cosEnd * endRho,
            centerY + thickness + sinEnd * endRho * distortion
        );
        path.ellipse(
            centerX, centerY + thickness,
            endRho, endRho * distortion,
            0, endAngle, startAngle, true
        );
        path.closePath();
    },

    topRenderer: function(path) {
        this.lidRenderer(path, 0);
    },

    bottomRenderer: function(path) {
        var attr = this.attr,
            none = Ext.util.Color.RGBA_NONE;

        if (attr.globalAlpha < 1 || attr.fillOpacity < 1 || attr.shadowColor !== none) {
            this.lidRenderer(path, attr.thickness);
        }
    },

    sideRenderer: function(path, position) {
        var attr = this.attr,
            margin = attr.margin,
            centerX = attr.centerX,
            centerY = attr.centerY,
            distortion = attr.distortion,
            baseRotation = attr.baseRotation,
            startAngle = attr.startAngle + baseRotation,
            endAngle = attr.endAngle + baseRotation,
            // eslint-disable-next-line max-len
            isFullPie = (!attr.startAngle && Ext.Number.isEqual(Math.PI * 2, attr.endAngle, 0.0000001)),
            thickness = attr.thickness,
            startRho = attr.startRho,
            endRho = attr.endRho,
            angle = (position === 'start' && startAngle) ||
                    (position === 'end' && endAngle),
            sin = Math.sin(angle),
            cos = Math.cos(angle),
            isTranslucent = attr.globalAlpha < 1,
            isVisible = position === 'start' && cos < 0 ||
                        position === 'end' && cos > 0 ||
                        isTranslucent,
            midAngle;

        if (isVisible && !isFullPie) {
            midAngle = (startAngle + endAngle) / 2;
            centerX += Math.cos(midAngle) * margin;
            centerY += Math.sin(midAngle) * margin * distortion;
            path.moveTo(
                centerX + cos * startRho,
                centerY + sin * startRho * distortion
            );
            path.lineTo(
                centerX + cos * endRho,
                centerY + sin * endRho * distortion
            );
            path.lineTo(
                centerX + cos * endRho,
                centerY + sin * endRho * distortion + thickness
            );
            path.lineTo(
                centerX + cos * startRho,
                centerY + sin * startRho * distortion + thickness
            );
            path.closePath();
        }
    },

    startRenderer: function(path) {
        this.sideRenderer(path, 'start');
    },

    endRenderer: function(path) {
        this.sideRenderer(path, 'end');
    },

    rimRenderer: function(path, radius, isDonut, isFront) {
        var me = this,
            attr = me.attr,
            margin = attr.margin,
            centerX = attr.centerX,
            centerY = attr.centerY,
            distortion = attr.distortion,
            baseRotation = attr.baseRotation,
            normalize = Ext.draw.sprite.AttributeParser.angle,
            startAngle = attr.startAngle + baseRotation,
            endAngle = attr.endAngle + baseRotation,
            // It's critical to use non-normalized start and end angles
            // for middle angle calculation. Consider a situation where the
            // start angle is +170 degrees and the end engle is -170 degrees
            // after normalization (the middle angle is 0 then, but it should be 180 degrees).
            midAngle = normalize((startAngle + endAngle) / 2),
            thickness = attr.thickness,
            isTranslucent = attr.globalAlpha < 1,
            isAllFront, isAllBack,
            params;

        me.bevelParams = [];

        startAngle = normalize(startAngle);
        endAngle = normalize(endAngle);

        centerX += Math.cos(midAngle) * margin;
        centerY += Math.sin(midAngle) * margin * distortion;

        isAllFront = startAngle >= 0 && endAngle >= 0;
        isAllBack = startAngle <= 0 && endAngle <= 0;

        function renderLeftFrontChunk() {
            path.ellipse(
                centerX, centerY + thickness,
                radius, radius * distortion,
                0, Math.PI, startAngle, true
            );
            path.lineTo(
                centerX + Math.cos(startAngle) * radius,
                centerY + Math.sin(startAngle) * radius * distortion
            );
            params = [
                centerX, centerY,
                radius, radius * distortion,
                0, startAngle, Math.PI, false
            ];

            if (!isDonut) {
                me.bevelParams.push(params);
            }

            path.ellipse.apply(path, params);
            path.closePath();
        }

        function renderRightFrontChunk() {
            path.ellipse(
                centerX, centerY + thickness,
                radius, radius * distortion,
                0, 0, endAngle, false
            );
            path.lineTo(
                centerX + Math.cos(endAngle) * radius,
                centerY + Math.sin(endAngle) * radius * distortion
            );
            params = [
                centerX, centerY,
                radius, radius * distortion,
                0, endAngle, 0, true
            ];

            if (!isDonut) {
                me.bevelParams.push(params);
            }

            path.ellipse.apply(path, params);
            path.closePath();
        }

        function renderLeftBackChunk() {
            path.ellipse(
                centerX, centerY + thickness,
                radius, radius * distortion,
                0, Math.PI, endAngle, false
            );
            path.lineTo(
                centerX + Math.cos(endAngle) * radius,
                centerY + Math.sin(endAngle) * radius * distortion
            );
            params = [
                centerX, centerY,
                radius, radius * distortion,
                0, endAngle, Math.PI, true
            ];

            if (isDonut) {
                me.bevelParams.push(params);
            }

            path.ellipse.apply(path, params);
            path.closePath();
        }

        function renderRightBackChunk() {
            path.ellipse(
                centerX, centerY + thickness,
                radius, radius * distortion,
                0, startAngle, 0, false
            );
            path.lineTo(
                centerX + radius,
                centerY
            );
            params = [
                centerX, centerY,
                radius, radius * distortion,
                0, 0, startAngle, true
            ];

            if (isDonut) {
                me.bevelParams.push(params);
            }

            path.ellipse.apply(path, params);
            path.closePath();
        }

        if (isFront) {
            if (!isDonut || isTranslucent) {
                if (startAngle >= 0 && endAngle < 0) {
                    renderLeftFrontChunk();
                }
                else if (startAngle <= 0 && endAngle > 0) {
                    renderRightFrontChunk();
                }
                else if (startAngle <= 0 && endAngle < 0) {
                    if (startAngle > endAngle) {
                        path.ellipse(
                            centerX, centerY + thickness,
                            radius, radius * distortion,
                            0, 0, Math.PI, false
                        );
                        path.lineTo(
                            centerX - radius,
                            centerY
                        );
                        params = [
                            centerX, centerY,
                            radius, radius * distortion,
                            0, Math.PI, 0, true
                        ];

                        if (!isDonut) {
                            me.bevelParams.push(params);
                        }

                        path.ellipse.apply(path, params);
                        path.closePath();
                    }
                }
                else { // startAngle >= 0 && endAngle > 0
                    // obtuse horseshoe-like slice with the gap facing forward
                    if (startAngle > endAngle) {
                        renderLeftFrontChunk();
                        renderRightFrontChunk();
                    }
                    else { // acute slice facing forward
                        params = [
                            centerX, centerY,
                            radius, radius * distortion,
                            0, startAngle, endAngle, false
                        ];

                        if (isAllFront && !isDonut || isAllBack && isDonut) {
                            me.bevelParams.push(params);
                        }

                        path.ellipse.apply(path, params);
                        path.lineTo(
                            centerX + Math.cos(endAngle) * radius,
                            centerY + Math.sin(endAngle) * radius * distortion + thickness
                        );
                        path.ellipse(
                            centerX, centerY + thickness,
                            radius, radius * distortion,
                            0, endAngle, startAngle, true
                        );
                        path.closePath();
                    }
                }
            }
        }
        else {
            if (isDonut || isTranslucent) {
                if (startAngle >= 0 && endAngle < 0) {
                    renderLeftBackChunk();
                }
                else if (startAngle <= 0 && endAngle > 0) {
                    renderRightBackChunk();
                }
                else if (startAngle <= 0 && endAngle < 0) {
                    if (startAngle > endAngle) {
                        renderLeftBackChunk();
                        renderRightBackChunk();
                    }
                    else {
                        path.ellipse(
                            centerX, centerY + thickness,
                            radius, radius * distortion,
                            0, startAngle, endAngle, false
                        );
                        path.lineTo(
                            centerX + Math.cos(endAngle) * radius,
                            centerY + Math.sin(endAngle) * radius * distortion
                        );
                        params = [
                            centerX, centerY,
                            radius, radius * distortion,
                            0, endAngle, startAngle, true
                        ];

                        if (isDonut) {
                            me.bevelParams.push(params);
                        }

                        path.ellipse.apply(path, params);
                        path.closePath();
                    }
                }
                else { // startAngle >= 0 && endAngle > 0
                    if (startAngle > endAngle) {
                        path.ellipse(
                            centerX, centerY + thickness,
                            radius, radius * distortion,
                            0, -Math.PI, 0, false
                        );
                        path.lineTo(
                            centerX + radius,
                            centerY
                        );
                        params = [
                            centerX, centerY,
                            radius, radius * distortion,
                            0, 0, -Math.PI, true
                        ];

                        if (isDonut) {
                            me.bevelParams.push(params);
                        }

                        path.ellipse.apply(path, params);
                        path.closePath();
                    }
                }
            }
        }
    },

    innerFrontRenderer: function(path) {
        this.rimRenderer(path, this.attr.startRho, true, true);
    },

    innerBackRenderer: function(path) {
        this.rimRenderer(path, this.attr.startRho, true, false);
    },

    outerFrontRenderer: function(path) {
        this.rimRenderer(path, this.attr.endRho, false, true);
    },

    outerBackRenderer: function(path) {
        this.rimRenderer(path, this.attr.endRho, false, false);
    }
});
