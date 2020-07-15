/**
 * @class Ext.draw.sprite.Text
 * @extends Ext.draw.sprite.Sprite
 *
 * A sprite that represents text.
 *
 *     @example
 *     Ext.create({
 *        xtype: 'draw',
 *        renderTo: document.body,
 *        width: 600,
 *        height: 400,
 *        sprites: [{
 *            type: 'text',
 *            x: 50,
 *            y: 50,
 *            text: 'Sencha',
 *            fontSize: 30,
 *            fillStyle: '#1F6D91'
 *        }]
 *     });
 */
/* eslint-disable indent */
Ext.define('Ext.draw.sprite.Text', function() {
    // Absolute font sizes.
    var fontSizes = {
        'xx-small': true,
        'x-small': true,
        'small': true,
        'medium': true,
        'large': true,
        'x-large': true,
        'xx-large': true
    },
    fontWeights = {
        normal: true,
        bold: true,
        bolder: true,
        lighter: true,
        100: true,
        200: true,
        300: true,
        400: true,
        500: true,
        600: true,
        700: true,
        800: true,
        900: true
    },
    textAlignments = {
        start: 'start',
        left: 'start',
        center: 'center',
        middle: 'center',
        end: 'end',
        right: 'end'
    },
    textBaselines = {
        top: 'top',
        hanging: 'hanging',
        middle: 'middle',
        center: 'middle',
        alphabetic: 'alphabetic',
        ideographic: 'ideographic',
        bottom: 'bottom'
    };

return {
    extend: 'Ext.draw.sprite.Sprite',
    requires: [
        'Ext.draw.TextMeasurer',
        'Ext.draw.Color'
    ],
    alias: 'sprite.text',
    type: 'text',
    lineBreakRe: /\r?\n/g,
    //<debug>
    statics: {
        /**
         * Debug rendering options:
         *
         * debug: {
         *     bbox: true // renders the bounding box of the text sprite
         * }
         *
         */
        debug: false,

        fontSizes: fontSizes,
        fontWeights: fontWeights,
        textAlignments: textAlignments,
        textBaselines: textBaselines
    },
    //</debug>
    inheritableStatics: {

        def: {
            animationProcessors: {
                text: 'text'
            },
            processors: {
                /**
                 * @cfg {Number} [x=0]
                 * The position of the sprite on the x-axis.
                 */
                x: 'number',

                /**
                 * @cfg {Number} [y=0]
                 * The position of the sprite on the y-axis.
                 */
                y: 'number',

                /**
                 * @cfg {String} [text='']
                 * The text represented in the sprite.
                 */
                text: 'string',

                /**
                 * @cfg {String/Number} [fontSize='10px']
                 * The size of the font displayed.
                 */
                fontSize: function(n) {
                    // Numbers as strings will be converted to numbers,
                    // null will be converted to 0.
                    if (Ext.isNumber(+n)) {
                        return n + 'px';
                    }
                    else if (n.match(Ext.dom.Element.unitRe)) {
                        return n;
                    }
                    else if (n in fontSizes) {
                        return n;
                    }
                },

                /**
                 * @cfg {String} [fontStyle='']
                 * The style of the font displayed. {normal, italic, oblique}
                 */
                fontStyle: 'enums(,italic,oblique)',

                /**
                 * @cfg {String} [fontVariant='']
                 * The variant of the font displayed. {normal, small-caps}
                 */
                fontVariant: 'enums(,small-caps)',

                /**
                 * @cfg {String} [fontWeight='']
                 * The weight of the font displayed. {normal, bold, bolder, lighter}
                 */
                fontWeight: function(n) {
                    if (n in fontWeights) {
                        return String(n);
                    }
                    else {
                        return '';
                    }
                },

                /**
                 * @cfg {String} [fontFamily='sans-serif']
                 * The family of the font displayed.
                 */
                fontFamily: 'string',

                /**
                 * @cfg {"left"/"right"/"center"/"start"/"end"} [textAlign='start']
                 * The alignment of the text displayed.
                 */
                textAlign: function(n) {
                    return textAlignments[n] || 'center';
                },

                /**
                 * @cfg {String} [textBaseline="alphabetic"]
                 * The baseline of the text displayed.
                 * {top, hanging, middle, alphabetic, ideographic, bottom}
                 */
                textBaseline: function(n) {
                    return textBaselines[n] || 'alphabetic';
                },

                //<debug>
                debug: 'default',
                //</debug>

                /**
                 * @cfg {String} [font='10px sans-serif']
                 * The font displayed.
                 */
                font: 'string'
            },
            aliases: {
                'font-size': 'fontSize',
                'font-family': 'fontFamily',
                'font-weight': 'fontWeight',
                'font-variant': 'fontVariant',
                'text-anchor': 'textAlign',
                'dominant-baseline': 'textBaseline'
            },
            defaults: {
                fontStyle: '',
                fontVariant: '',
                fontWeight: '',
                fontSize: '10px',
                fontFamily: 'sans-serif',
                font: '10px sans-serif',
                textBaseline: 'alphabetic',
                textAlign: 'start',
                strokeStyle: 'rgba(0, 0, 0, 0)',
                fillStyle: '#000',
                x: 0,
                y: 0,
                text: ''
            },
            triggers: {
                fontStyle: 'fontX,bbox',
                fontVariant: 'fontX,bbox',
                fontWeight: 'fontX,bbox',
                fontSize: 'fontX,bbox',
                fontFamily: 'fontX,bbox',
                font: 'font,bbox,canvas',
                textBaseline: 'bbox',
                textAlign: 'bbox',
                x: 'bbox',
                y: 'bbox',
                text: 'bbox'
            },
            updaters: {
                fontX: 'makeFontShorthand',
                font: 'parseFontShorthand'
            }
        }
    },

    config: {
        /**
         * @private
         * If the value is boolean, it overrides the TextMeasurer's 'precise' config
         * (for the given sprite only).
         */
        preciseMeasurement: undefined
    },

    constructor: function(config) {
        var key;

        if (config && config.font) {
            config = Ext.clone(config);

            for (key in config) {
                if (key !== 'font' && key.indexOf('font') === 0) {
                    delete config[key];
                }
            }
        }

        Ext.draw.sprite.Sprite.prototype.constructor.call(this, config);
    },

    // Maps values to font properties they belong to.
    fontValuesMap: {
        // Skip 'normal' and 'inherit' values, as the first one
        // is the default and the second one has no meaning in Canvas.
        'italic': 'fontStyle',
        'oblique': 'fontStyle',

        'small-caps': 'fontVariant',

        'bold': 'fontWeight',
        'bolder': 'fontWeight',
        'lighter': 'fontWeight',
        '100': 'fontWeight',
        '200': 'fontWeight',
        '300': 'fontWeight',
        '400': 'fontWeight',
        '500': 'fontWeight',
        '600': 'fontWeight',
        '700': 'fontWeight',
        '800': 'fontWeight',
        '900': 'fontWeight',

        // Absolute font sizes.
        'xx-small': 'fontSize',
        'x-small': 'fontSize',
        'small': 'fontSize',
        'medium': 'fontSize',
        'large': 'fontSize',
        'x-large': 'fontSize',
        'xx-large': 'fontSize'
        // Relative font sizes like 'smaller' and 'larger'
        // have no meaning, and are not included.
    },

    makeFontShorthand: function(attr) {
        var parts = [];

        if (attr.fontStyle) {
            parts.push(attr.fontStyle);
        }

        if (attr.fontVariant) {
            parts.push(attr.fontVariant);
        }

        if (attr.fontWeight) {
            parts.push(attr.fontWeight);
        }

        if (attr.fontSize) {
            parts.push(attr.fontSize);
        }

        if (attr.fontFamily) {
            parts.push(attr.fontFamily);
        }

        this.setAttributes({
            font: parts.join(' ')
        }, true);
    },

    // For more info see:
    // http://www.w3.org/TR/CSS21/fonts.html#font-shorthand
    parseFontShorthand: function(attr) {
        var value = attr.font,
            ln = value.length,
            changes = {},
            dispatcher = this.fontValuesMap,
            start = 0,
            end, slashIndex, part, fontProperty;

        while (start < ln && end !== -1) {
            end = value.indexOf(' ', start);

            if (end < 0) {
                part = value.substr(start);
            }
            else if (end > start) {
                part = value.substr(start, end - start);
            }
            else {
                continue;
            }

            // Since Canvas fillText doesn't support multi-line text,
            // it is assumed that line height is never specified, i.e.
            // in entries like these the part after slash is omitted:
            // 12px/14px sans-serif
            // x-large/110% "New Century Schoolbook", serif
            slashIndex = part.indexOf('/');

            if (slashIndex > 0) {
                part = part.substr(0, slashIndex);
            }
            else if (slashIndex === 0) {
                continue;
            }

            // All optional font properties (fontStyle, fontVariant or fontWeight) can be 'normal'.
            // They can go in any order. Which ones are 'normal' is determined by elimination.
            // E.g. if only fontVariant is specified, then 'normal' applies to fontStyle
            // and fontWeight.
            // If none are explicitly mentioned, then all are 'normal'.
            if (part !== 'normal' && part !== 'inherit') {
                fontProperty = dispatcher[part];

                if (fontProperty) {
                    changes[fontProperty] = part;
                }
                 else if (part.match(Ext.dom.Element.unitRe)) {
                    changes.fontSize = part;
                }
                else { // Assuming that font family always goes last in the font shorthand.
                    changes.fontFamily = value.substr(start);
                    break;
                }
            }

            start = end + 1;
        }

        if (!changes.fontStyle) {
            changes.fontStyle = '';   // same as 'normal'
        }

        if (!changes.fontVariant) {
            changes.fontVariant = ''; // same as 'normal'
        }

        if (!changes.fontWeight) {
            changes.fontWeight = '';  // same as 'normal'
        }

        this.setAttributes(changes, true);
    },

    fontProperties: {
        fontStyle: true,
        fontVariant: true,
        fontWeight: true,
        fontSize: true,
        fontFamily: true
    },

    setAttributes: function(changes, bypassNormalization, avoidCopy) {
        var key, obj;

        // Discard individual font properties if 'font' shorthand was also provided.

        // Example: a user provides a config for chart series labels, using the font
        // shorthand, which is parsed into individual font properties and corresponding
        // sprite attributes are set. Then a theme is applied to the chart, and
        // individual font properties from the theme make up the new font shorthand
        // that overrides the previous one. In other words, no matter what font
        // the user has specified, theme font will be used.

        // This workaround relies on the fact that the theme merges its own config with
        // the user config (where user config values take over the same theme config
        // values). So both user font shorthand and individual font properties from
        // the theme are present in the resulting config (since there are no collisions),
        // which ends up here as the 'changes' parameter.

        // If the user wants their font config to merged with the the theme's font config,
        // instead of taking over it, individual font properties should be used
        // by the user as well.

        if (changes && changes.font) {
            obj = {};

            for (key in changes) {
                if (!(key in this.fontProperties)) {
                    obj[key] = changes[key];
                }
            }

            changes = obj;
        }

        this.callParent([changes, bypassNormalization, avoidCopy]);
    },

    // Overriding the getBBox method of the abstract sprite here to always
    // recalculate the bounding box of the text in flipped RTL mode
    // because in that case the position of the sprite depends not just on
    // the value of its 'x' attribute, but also on the width of the surface
    // the sprite belongs to.
    getBBox: function(isWithoutTransform) {
        var me = this,
            plain = me.attr.bbox.plain,
            surface = me.getSurface();

        //<debug>
        // The sprite's bounding box won't account for RTL if it doesn't
        // belong to a surface.
        // if (!surface) {
        //    Ext.raise("The sprite does not belong to a surface.");
        // }
        //</debug>
        if (plain.dirty) {
            me.updatePlainBBox(plain);
            plain.dirty = false;
        }

        if (surface && surface.getInherited().rtl && surface.getFlipRtlText()) {
            // Since sprite's attributes haven't actually changed at this point,
            // and we just want to update the position of its bbox
            // based on surface's width, there's no reason to perform
            // expensive text measurement operation here,
            // so we can use the result of the last measurement instead.
            me.updatePlainBBox(plain, true);
        }

        return me.callParent([isWithoutTransform]);
    },

    rtlAlignments: {
        start: 'end',
        center: 'center',
        end: 'start'
    },

    updatePlainBBox: function(plain, useOldSize) {
        var me = this,
            attr = me.attr,
            x = attr.x,
            y = attr.y,
            dx = [],
            font = attr.font,
            text = attr.text,
            baseline = attr.textBaseline,
            alignment = attr.textAlign,
            precise = me.getPreciseMeasurement(),
            size, textMeasurerPrecision;

        if (useOldSize && me.oldSize) {
            size = me.oldSize;
        }
        else {
            textMeasurerPrecision = Ext.draw.TextMeasurer.precise;

            if (Ext.isBoolean(precise)) {
                Ext.draw.TextMeasurer.precise = precise;
            }

            size = me.oldSize = Ext.draw.TextMeasurer.measureText(text, font);
            Ext.draw.TextMeasurer.precise = textMeasurerPrecision;
        }

        // eslint-disable-next-line vars-on-top, one-var
        var surface = me.getSurface(),
            isRtl = (surface && surface.getInherited().rtl) || false,
            flipRtlText = isRtl && surface.getFlipRtlText(),
            sizes = size.sizes,
            blockHeight = size.height,
            blockWidth = size.width,
            ln = sizes ? sizes.length : 0,
            lineWidth, rect,
            i = 0;

        // To get consistent results in all browsers we don't apply textAlign
        // and textBaseline attributes of the sprite to context, so text is always
        // left aligned and has an alphabetic baseline.
        //
        // Instead we have to calculate the horizontal offset of each line
        // based on sprite's textAlign, and the vertical offset of the bounding box
        // based on sprite's textBaseline.
        //
        // These offsets are then used by the sprite's 'render' method
        // to position text properly.

        switch (baseline) {
            case 'hanging' :
            case 'top':
                break;
            case 'ideographic' :
            case 'bottom' :
                y -= blockHeight;
                break;
            case 'alphabetic' :
                y -= blockHeight * 0.8;
                break;
            case 'middle' :
                y -= blockHeight * 0.5;
                break;
        }

        if (flipRtlText) {
            rect = surface.getRect();
            x = rect[2] - rect[0] - x;
            alignment = me.rtlAlignments[alignment];
        }

        switch (alignment) {
            case 'start':
                if (isRtl) {
                    for (; i < ln; i++) {
                        lineWidth = sizes[i].width;
                        dx.push(-(blockWidth - lineWidth));
                    }
                }

                break;
            case 'end' :
                x -= blockWidth;

                if (isRtl) {
                    break;
                }

                for (; i < ln; i++) {
                    lineWidth = sizes[i].width;
                    dx.push(blockWidth - lineWidth);
                }

                break;
            case 'center' :
                x -= blockWidth * 0.5;

                for (; i < ln; i++) {
                    lineWidth = sizes[i].width;
                    dx.push((isRtl ? -1 : 1) * (blockWidth - lineWidth) * 0.5);
                }

                break;
        }

        attr.textAlignOffsets = dx;

        plain.x = x;
        plain.y = y;
        plain.width = blockWidth;
        plain.height = blockHeight;
    },

    setText: function(text) {
        this.setAttributes({ text: text }, true);
    },

    render: function(surface, ctx, rect) {
        var me = this,
            attr = me.attr,
            mat = Ext.draw.Matrix.fly(attr.matrix.elements.slice(0)),
            bbox = me.getBBox(true),
            dx = attr.textAlignOffsets,
            none = Ext.util.Color.RGBA_NONE,
            x, y, i, lines, lineHeight;

        if (attr.text.length === 0) {
            return;
        }

        lines = attr.text.split(me.lineBreakRe);
        lineHeight = bbox.height / lines.length;
        // Simulate textBaseline and textAlign.
        x = attr.bbox.plain.x;
        // lineHeight * 0.78 is the approximate distance between the top
        // and the alphabetic baselines
        y = attr.bbox.plain.y + lineHeight * 0.78;
        mat.toContext(ctx);

        if (surface.getInherited().rtl) {
            // Canvas element in RTL mode automatically flips text alignment.
            // Here we compensate for that change.
            // So text is still positioned and aligned as in the LTR mode,
            // but the direction of the text is RTL.
            x += attr.bbox.plain.width;
        }

        for (i = 0; i < lines.length; i++) {
            if (ctx.fillStyle !== none) {
                ctx.fillText(lines[i], x + (dx[i] || 0), y + lineHeight * i);
            }

            if (ctx.strokeStyle !== none) {
                ctx.strokeText(lines[i], x + (dx[i] || 0), y + lineHeight * i);
            }
        }

        //<debug>
        // eslint-disable-next-line vars-on-top
        var debug = attr.debug || this.statics().debug || Ext.draw.sprite.Sprite.debug;

        if (debug) {
            // This assumes no part of the sprite is rendered after this call.
            // If it is, we need to re-apply transformations.
            // But the bounding box is already transformed, so we remove the transformation.
            this.attr.inverseMatrix.toContext(ctx);

            if (debug.bbox) {
                me.renderBBox(surface, ctx);
            }
        }
        //</debug>
    }
};

});
