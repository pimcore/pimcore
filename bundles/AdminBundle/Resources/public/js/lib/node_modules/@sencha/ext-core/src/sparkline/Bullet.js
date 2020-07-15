/**
 * @class Ext.sparkline.Bullet
 *
 * Plots a bullet graph based upon the input {@link #values} array.
 *
 * See <a href="http://en.wikipedia.org/wiki/Bullet_graph">Bullet graphs Wikipedia Page</a>
 * for more information.
 *
 * The first value should be the target value. If there is no target value, it should be `null`.
 * The second value should be the performance value. If there is no performance value, it should be
 * specified as `null`.
 *
 * An example value:
 *
 *     // Target 10
 *     // Performance 12
 *     // Ranges 12,9,7
 *     [10, 12, 12, 9, 7]
 *
 * See {@link Ext.sparkline.Base the base class} for a simple example.
 */
Ext.define('Ext.sparkline.Bullet', {
    extend: 'Ext.sparkline.Base',

    alias: 'widget.sparklinebullet',

    config: {

        /**
         * @cfg {String} [targetColor=#f33] The colour of the vertical target marker.
         */
        targetColor: '#f33',

        /**
         * @cfg {Number} [targetWidth=3] Width of the target bar in pixels.
         */
        targetWidth: 3,

        /**
         * @cfg {String} [performanceColor=#33f] The color of the performance measure
         * horizontal bar.
         */
        performanceColor: '#33f',

        /**
         * @cfg {String[]} [rangeColors] An array of colors to use for each qualitative range
         * background color.
         */
        rangeColors: ['#d3dafe', '#a8b6ff', '#7f94ff'],

        /**
         * @cfg {Number} [base] Set this to a number to change the base start number.
         */
        base: null
    },

    tipTpl: ['{fieldkey:this.fields} - {value}', {
        fields: function(v) {
            if (v === 'r') {
                return 'Range';
            }

            if (v === 'p') {
                return 'Performance';
            }

            if (v === 't') {
                return 'Target';
            }
        }
    }],

    // Ensure values is an array of normalized values
    applyValues: function(newValues) {
        newValues = Ext.Array.map(Ext.Array.from(newValues), this.normalizeValue);

        this.disabled = !(newValues && newValues.length);
        this.updateConfigChange();

        return newValues;
    },

    onUpdate: function() {
        var me = this,
            values = me.values,
            min, max, vals,
            base = me.getBase();

        me.callParent(arguments);

        // target or performance could be null
        vals = values.slice();
        vals[0] = vals[0] === null ? vals[2] : vals[0];
        vals[1] = values[1] === null ? vals[2] : vals[1];
        min = Math.min.apply(Math, values);
        max = Math.max.apply(Math, values);

        if (base == null) {
            min = min < 0 ? min : 0;
        }
        else {
            min = base;
        }

        me.min = min;
        me.max = max;
        me.range = max - min;
        me.shapes = {};
        me.valueShapes = {};
        me.regiondata = {};

        if (!values.length) {
            me.disabled = true;
        }
    },

    getRegion: function(x, y) {
        var shapeid = this.canvas.getShapeAt(x, y);

        return (shapeid !== undefined && this.shapes[shapeid] !== undefined)
            ? this.shapes[shapeid]
            : undefined;
    },

    getRegionFields: function(region) {
        return {
            fieldkey: region.substr(0, 1),
            value: this.values[parseInt(region.substr(1), 10)],
            region: region
        };
    },

    renderHighlight: function(region) {
        var me = this,
            valueShapes = me.valueShapes,
            shapes = me.shapes,
            shapeId = valueShapes[region],
            shape;

        delete shapes[shapeId];

        switch (region.substr(0, 1)) {
            case 'r':
                shape = me.renderRange(parseInt(region.substr(1), 10), true);
                break;
            case 'p':
                shape = me.renderPerformance(true);
                break;
            case 't':
                shape = me.renderTarget(true);
                break;
        }

        valueShapes[region] = shape.id;
        shapes[shape.id] = region;
        me.canvas.replaceWithShape(shapeId, shape);
    },

    renderRange: function(region, highlight) {
        var me = this,
            rangeval = me.values[region],
            rangewidth = Math.round(me.getWidth() * ((rangeval - me.min) / me.range)),
            colors = me.getRangeColors(),
            color = colors[Math.min(region - 2, colors.length - 1)];

        if (highlight) {
            color = me.calcHighlightColor(color);
        }

        return me.canvas.drawRect(0, 0, rangewidth - 1, me.getHeight() - 1, color, color);
    },

    renderPerformance: function(highlight) {
        var perfval = this.values[1],
            perfwidth = Math.round(this.getWidth() * ((perfval - this.min) / this.range)),
            color = this.getPerformanceColor();

        if (highlight) {
            color = this.calcHighlightColor(color);
        }

        return this.canvas.drawRect(0, Math.round(this.getHeight() * 0.3), perfwidth - 1,
                                    Math.round(this.getHeight() * 0.4) - 1, color, color);
    },

    renderTarget: function(highlight) {
        var targetval = this.values[0],
            targetWidth = this.getTargetWidth(),
            x = Math.round(this.getWidth() * ((targetval - this.min) / this.range) - (targetWidth / 2)), // eslint-disable-line max-len
            targettop = Math.round(this.getHeight() * 0.10),
            targetheight = this.getHeight() - (targettop * 2),
            color = this.getTargetColor();

        if (highlight) {
            color = this.calcHighlightColor(color);
        }

        return this.canvas.drawRect(x, targettop, targetWidth - 1, targetheight - 1, color, color);
    },

    renderGraph: function() {
        var me = this,
            vlen = me.values.length,
            canvas = me.canvas,
            i, shape,
            shapes = me.shapes || (me.shapes = {}),
            valueShapes = me.valueShapes || (me.valueShapes = {});

        if (!me.callParent()) {
            return;
        }

        for (i = 2; i < vlen; i++) {
            shape = me.renderRange(i).append();
            shapes[shape.id] = 'r' + i;
            valueShapes['r' + i] = shape.id;
        }

        if (me.values[1] !== null) {
            shape = me.renderPerformance().append();
            shapes[shape.id] = 'p1';
            valueShapes.p1 = shape.id;
        }

        if (me.values[0] !== null) {
            shape = this.renderTarget().append();
            shapes[shape.id] = 't0';
            valueShapes.t0 = shape.id;
        }

        // If mouse is over, apply the highlight
        if (me.currentPageXY && me.canvasRegion.contains(me.currentPageXY)) {
            me.updateDisplay();
        }

        canvas.render();
    },

    privates: {
        isValidRegion: function(region, values) {
            return parseInt(region.substr(1), 10) < values.length;
        }
    }
});
