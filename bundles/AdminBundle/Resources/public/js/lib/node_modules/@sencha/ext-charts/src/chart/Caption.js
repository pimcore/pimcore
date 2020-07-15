/**
 *  Chart captions can be used to place titles, subtitles, credits and other captions
 *  inside a chart. Please see the chart's {@link Ext.chart.AbstractChart#captions}
 *  config documentation for the general description of the way captions work, and
 *  refer to the documentation of this class' configs for details.
 */
Ext.define('Ext.chart.Caption', {
    mixins: [
        'Ext.mixin.Observable',
        'Ext.mixin.Bindable'
    ],

    isCaption: true,

    config: {
        /**
         * The weight controls the order in which the captions are created.
         * Captions with lower weights are created first.
         * This affects chart's layout. For example, if two captions are docked
         * to the 'top', the one with the lower weight will end up on top
         * of the other.
         */
        weight: 0,

        /**
         * @cfg {String} text
         * The text displayed by the caption.
         * Multi-line captions are allowed, e.g.:
         *
         *     captions: {
         *         title: {
         *             text: 'India\'s tiger population\n'
         *                 + 'from 1970 to 2015'
         *         }
         *     }
         *
         */
        text: '',

        /**
         * @cfg {'left'/'center'/'right'} [align='center']
         * Determines the horizontal alignment of the caption's text.
         */
        align: 'center',

        /**
         * @cfg {'series'/'chart'} [alignTo='series']
         * Whether to align the caption to the 'series' (default) or the 'chart'.
         */
        alignTo: 'series',

        /**
         * @cfg {Number} padding
         * The uniform padding applied to both top and bottom of the caption's text.
         */
        padding: 0,

        /**
         * @cfg {Boolean} [hidden=false]
         * Controls the visibility of the caption.
         */
        hidden: false,

        /**
         * @cfg {'top'/'bottom'} [docked='top']
         * The position of the caption in a chart.
         */
        docked: 'top',

        /**
         * @cfg {Object} style
         * Style attributes for the caption's text.
         * All attributes that are valid {@link Ext.draw.sprite.Text text sprite} attributes
         * are valid here. However, only font attributes (such as `fontSize`, `fontFamily`, ...),
         * color attributes (such as `fillStyle`) and `textAlign` attribute are guaranteed to
         * produce correct behavior. For example, transform attributes are not officially supported.
         */
        style: {
            fontSize: '14px',
            fontWeight: 'bold',
            fontFamily: 'Verdana, Aria, sans-serif'
        },

        /**
         * @private
         * @cfg {Ext.chart.AbstractChart} chart
         * The chart the label belongs to.
         */
        chart: null,

        /**
         * @private
         * The text sprite used to render caption's text.
         */
        sprite: {
            type: 'text',
            preciseMeasurement: true,
            zIndex: 10
        },

        //<debug>
        /**
         * @private
         * @cfg {Boolean} debug
         * Whether to show the bounding boxes or not.
         */
        debug: false,
        //</debug>

        /**
         * @private
         * The logical rect of the caption in the `surfaceName` surface.
         */
        rect: null
    },

    surfaceName: 'caption',

    constructor: function(config) {
        var me = this,
            id;

        if ('id' in config) {
            id = config.id;
        }
        else if ('id' in me.config) {
            id = me.config.id;
        }
        else {
            id = me.getId();
        }

        me.setId(id);

        me.mixins.observable.constructor.call(me, config);
        me.initBindable();
    },

    updateChart: function() {
        if (!this.isConfiguring) {
            // Re-create caption's sprite in another chart.
            this.setSprite({
                type: 'text'
            });
        }
    },

    applySprite: function(sprite) {
        var me = this,
            chart = me.getChart(),
            surface = me.surface = chart.getSurface(me.surfaceName);

        //<debug>
        me.rectSprite = surface.add({
            type: 'rect',
            fillStyle: 'yellow',
            strokeStyle: 'red'
        });

        //</debug>
        return sprite && surface.add(sprite);
    },

    updateSprite: function(sprite, oldSprite) {
        if (oldSprite) {
            oldSprite.destroy();
        }
    },

    updateText: function(text) {
        this.getSprite().setAttributes({
            text: text
        });
    },

    updateStyle: function(style) {
        this.getSprite().setAttributes(style);
    },

    //<debug>
    updateDebug: function(debug) {
        var me = this,
            sprite = me.getSprite();

        if (debug && !me.rectSprite) {
            me.rectSprite = me.surface.add({
                type: 'rect',
                fillStyle: 'yellow',
                strokeStyle: 'red'
            });
        }

        if (sprite) {
            sprite.setAttributes({
                debug: debug ? { bbox: true } : null
            });
        }

        if (me.rectSprite) {
            me.rectSprite.setAttributes({
                hidden: !debug
            });
        }

        if (!me.isConfiguring) {
            me.surface.renderFrame();
        }
    },
    //</debug>

    updateRect: function(rect) {
        if (this.rectSprite) {
            this.rectSprite.setAttributes({
                x: rect[0],
                y: rect[1],
                width: rect[2],
                height: rect[3]
            });
        }
    },

    updateDocked: function() {
        var chart = this.getChart();

        if (chart && !this.isConfiguring) {
            chart.scheduleLayout();
        }
    },

    /**
     * @private
     * Computes and sets the caption's rect.
     * Shrinks the given chart rect to accomodate the caption.
     * The chart rect is [top, left, width, height] in chart's
     * body element coordinates.
     * The shrink rect is {left, top, right, bottom} in `caption`
     * surface coordinates.
     */
    computeRect: function(chartRect, shrinkRect) {
        if (this.getHidden()) {
            return null;
        }

        // eslint-disable-next-line vars-on-top
        var rect = [0, 0, chartRect[2], 0],
            docked = this.getDocked(),
            padding = this.getPadding(),
            textSize = this.getSprite().getBBox(),
            height = textSize.height + padding * 2;

        switch (docked) {
            case 'top':
                rect[1] = shrinkRect.top;
                rect[3] = height;

                chartRect[1] += height;
                chartRect[3] -= height;

                shrinkRect.top += height;
                break;

            case 'bottom':
                chartRect[3] -= height;
                shrinkRect.bottom -= height;

                rect[1] = shrinkRect.bottom;
                rect[3] = height;
                break;
        }

        this.setRect(rect);
    },

    alignRect: function(seriesRect) {
        var surfaceRect = this.surface.getRect(),
            rect = this.getRect();

        rect[0] = seriesRect[0] - surfaceRect[0];
        rect[2] = seriesRect[2];

        // Slice to trigger the applier/updater.
        this.setRect(rect.slice());
    },

    performLayout: function() {
        var me = this,
            rect = me.getRect(),
            x = rect[0],
            y = rect[1],
            width = rect[2],
            height = rect[3],
            sprite = me.getSprite(),
            tx = sprite.attr.translationX,
            ty = sprite.attr.translationY,
            bbox = sprite.getBBox(),
            align = me.getAlign(),
            dx, dy;

        switch (align) {
            case 'left':
                dx = x - bbox.x;
                break;

            case 'right':
                dx = (x + width) - (bbox.x + bbox.width);
                break;

            case 'center':
                dx = x + (width - bbox.width) / 2 - bbox.x;
                break;
        }

        dy = y + (height - bbox.height) / 2 - bbox.y;

        sprite.setAttributes({
            translationX: tx + dx,
            translationY: ty + dy
        });
    },

    destroy: function() {
        var me = this;

        //<debug>
        if (me.rectSprite) {
            me.rectSprite.destroy();
        }

        //</debug>
        me.getSprite().destroy();

        me.callParent();
    }

});
