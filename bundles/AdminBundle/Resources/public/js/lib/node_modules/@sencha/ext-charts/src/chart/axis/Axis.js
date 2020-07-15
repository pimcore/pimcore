/**
 * @class Ext.chart.axis.Axis
 *
 * Defines axis for charts.
 *
 * Using the current model, the type of axis can be easily extended. By default, Sencha Charts 
 * provide three different types of axis:
 *
 *  * **numeric** - the data attached to this axis is numeric and continuous.
 *  * **time** - the data attached to this axis is (or gets converted into) a date/time value; 
 *               it is continuous.
 *  * **category** - the data attached to this axis belongs to a finite set. The data points
 *                   are evenly placed along the axis.
 *
 * The behavior of an axis can be easily changed by setting different types of axis layout and 
 * axis segmenter to the axis.
 *
 * Axis layout defines how the data points are placed. Using continuous layout, the data points 
 * will be distributed by the numeric value. Using discrete layout the data points will be spaced 
 * evenly. Furthermore, if you want to combine the data points with the duplicate values in a 
 * discrete layout, you should use combineDuplicate layout.
 *
 * Segmenter defines the way to segment data range. For example, if you have a Date-type data range 
 * from Jan 1, 1997 to Jan 1, 2017, the segmenter will segement the data range into years, months or
 * days based on the current zooming level.
 *
 * It is possible to write custom axis layouts and segmenters to extends this behavior by simply 
 * implementing interfaces {@link Ext.chart.axis.layout.Layout} and
 * {@link Ext.chart.axis.segmenter.Segmenter}.
 *
 * Here's an example for the axes part of a chart definition:
 * An example of axis for a series (in this case for an area chart that has multiple layers of 
 * yFields) could be:
 *
 *     axes: [{
 *         type: 'numeric',
 *         position: 'left',
 *         title: 'Number of Hits',
 *         grid: {
 *             odd: {
 *                 opacity: 1,
 *                 fill: '#ddd',
 *                 stroke: '#bbb',
 *                 lineWidth: 1
 *             }
 *         },
 *         minimum: 0
 *     }, {
 *         type: 'category',
 *         position: 'bottom',
 *         title: 'Month of the Year',
 *         grid: true,
 *         label: {
 *             rotate: {
 *                 degrees: 315
 *             }
 *         }
 *     }]
 *
 * In this case we use a `numeric` axis for displaying the values of the Area series and a 
 * `category` axis for displaying the names of the store elements. The numeric axis is placed 
 * on the left of the screen, while the category axis is placed at the bottom of the chart.
 * Both the category and numeric axes have `grid` set, which means that horizontal and vertical 
 * lines will cover the chart background. In the category axis the labels will be rotated so 
 * they can fit the space better.
 */
Ext.define('Ext.chart.axis.Axis', {
    xtype: 'axis',

    mixins: {
        observable: 'Ext.mixin.Observable'
    },

    requires: [
        'Ext.chart.axis.sprite.Axis',
        'Ext.chart.axis.segmenter.*',
        'Ext.chart.axis.layout.*',
        'Ext.chart.Util'
    ],

    isAxis: true,

    /**
     * @event rangechange
     * Fires when the {@link Ext.chart.axis.Axis#range range} of the axis  changes.
     * @param {Ext.chart.axis.Axis} axis
     * @param {Array} range
     * @param {Array} oldRange
     */

    /**
     * @event visiblerangechange
     * Fires when the {@link #visibleRange} of the axis changes.
     * @param {Ext.chart.axis.Axis} axis
     * @param {Array} visibleRange
     */

    /**
     * @cfg {String} id
     * The **unique** id of this axis instance.
     */

    config: {
        /**
         * @cfg {String} position
         * Where to set the axis. Available options are `left`, `bottom`, `right`, `top`, `radial`,
         * and `angular`.
         */
        position: 'bottom',

        /**
         * @cfg {Array} fields
         * An array containing the names of the record fields which should be mapped along the axis.
         * This is optional if the binding between series and fields is clear.
         */
        fields: [],

        /**
         * @cfg {Object} label
         *
         * The label configuration object for the Axis. This object may include style attributes
         * like `spacing`, `padding`, `font` that receives a string or number and
         * returns a new string with the modified values.
         *
         * For more supported values, see the configurations for {@link Ext.chart.sprite.Label}.
         */
        label: undefined,

        /**
         * @cfg {Object} grid
         * The grid configuration object for the Axis style. Can contain `stroke` or `fill`
         * attributes. Also may contain an `odd` or `even` property in which you only style things
         * on odd or even rows. For example:
         *
         *
         *     grid {
         *         odd: {
         *             stroke: '#555'
         *         },
         *         even: {
         *             stroke: '#ccc'
         *         }
         *     }
         */
        grid: false,

        /**
         * @cfg {Array|Object} limits
         * The limit lines configuration for the axis.
         * For example:
         *
         *     limits: [{
         *         value: 50,
         *         line: {
         *             strokeStyle: 'red',
         *             lineDash: [6, 3],
         *             title: {
         *                 text: 'Monthly minimum',
         *                 fontSize: 14
         *             }
         *         }
         *     }]
         */
        limits: null,

        /**
         * @cfg {Function} renderer Allows to change the text shown next to the tick.
         * @param {Ext.chart.axis.Axis} axis The axis.
         * @param {String/Number} label The label.
         * @param {Object} layoutContext The object that holds calculated positions
         * of axis' ticks based on current layout, segmenter, axis length and configuration.
         * @param {String/Number/null} lastLabel The last label (if any).
         * @return {String} The label to display.
         * @controllable
         */
        renderer: null,

        /**
         * @protected
         * @cfg {Ext.chart.AbstractChart} chart The Chart that the Axis is bound.
         */
        chart: null,

        /**
         * @cfg {Object} style
         * The style for the axis line and ticks.
         * Refer to the {@link Ext.chart.axis.sprite.Axis}
         */
        style: null,

        /**
         * @cfg {Number} margin
         * The margin of the axis. Used to control the spacing between axes in charts with multiple
         * axes. Unlike CSS where the margin is added on all 4 sides of an element, the `margin`
         * is the total space that is added horizontally for a vertical axis, vertically
         * for a horizontal axis, and radially for an angular axis.
         */
        margin: 0,

        /**
         * @cfg {Number} [titleMargin=4]
         * The margin around the axis title. Unlike CSS where the margin is added on all 4
         * sides of an element, the `titleMargin` is the total space that is added horizontally
         * for a vertical title and vertically for an horizontal title, with half the `titleMargin`
         * being added on either side.
         */
        titleMargin: 4,

        /**
         * @cfg {Object} background
         * The background config for the axis surface.
         */
        background: null,

        /**
         * @cfg {Number} minimum
         * The minimum value drawn by the axis. If not set explicitly, the axis
         * minimum will be calculated automatically.
         */
        minimum: NaN,

        /**
         * @cfg {Number} maximum
         * The maximum value drawn by the axis. If not set explicitly, the axis
         * maximum will be calculated automatically.
         */
        maximum: NaN,

        /**
         * @cfg {Boolean} reconcileRange
         * If 'true' the range of the axis will be a union of ranges
         * of all the axes with the same direction. Defaults to 'false'.
         */
        reconcileRange: false,

        /**
         * @cfg {Number} minZoom
         * The minimum zooming level for axis.
         */
        minZoom: 1,

        /**
         * @cfg {Number} maxZoom
         * The maximum zooming level for axis.
         */
        maxZoom: 10000,

        /**
         * @cfg {Object|Ext.chart.axis.layout.Layout} layout
         * The axis layout config. See {@link Ext.chart.axis.layout.Layout}
         */
        layout: 'continuous',

        /**
         * @cfg {Object|Ext.chart.axis.segmenter.Segmenter} segmenter
         * The segmenter config. See {@link Ext.chart.axis.segmenter.Segmenter}
         */
        segmenter: 'numeric',

        /**
         * @cfg {Boolean} hidden
         * Indicate whether to hide the axis.
         * If the axis is hidden, one of the axis line, ticks, labels or the title will be shown and
         * no margin will be taken.
         * The coordination mechanism works fine no matter if the axis is hidden.
         */
        hidden: false,

        /**
         * @cfg {Number} [majorTickSteps=0]
         * Forces the number of major ticks to the specified value.
         * Both {@link #minimum} and {@link #maximum} should be specified.
         */
        majorTickSteps: 0,

        /**
         * @cfg {Number} [minorTickSteps=0]
         * The number of small ticks between two major ticks.
         */
        minorTickSteps: 0,

        /**
         * @cfg {Boolean} adjustByMajorUnit
         * Whether to make the auto-calculated minimum and maximum of the axis
         * a multiple of the interval between the major ticks of the axis.
         * If {@link #majorTickSteps}, {@link #minimum} or {@link #maximum}
         * configs have been set, this config will be ignored.
         * Defaults to 'true'.
         * Note: this config has no effect if the axis is {@link #hidden}.
         */
        adjustByMajorUnit: true,

        /**
         * @cfg {String|Object} title
         * The title for the Axis.
         * If given a String, the 'text' attribute of the title sprite will be set,
         * otherwise the style will be set.
         */
        title: null,

        /**
         * @private
         * @cfg {Number} [expandRangeBy=0]
         */
        expandRangeBy: 0,

        /**
         * @private
         * @cfg {Number} length
         * Length of the axis position. Equals to the size of inner rect on the docking side
         * of this axis.
         * WARNING: Meant to be set automatically by chart. Do not set it manually.
         */
        length: 0,

        /**
         * @private
         * @cfg {Array} center
         * Center of the polar axis.
         * WARNING: Meant to be set automatically by chart. Do not set it manually.
         */
        center: null,

        /**
         * @private
         * @cfg {Number} radius
         * Radius of the polar axis.
         * WARNING: Meant to be set automatically by chart. Do not set it manually.
         */
        radius: null,

        /**
         * @private
         */
        totalAngle: Math.PI,

        /**
         * @private
         * @cfg {Number} rotation
         * Rotation of the polar axis in radians.
         * WARNING: Meant to be set automatically by chart. Do not set it manually.
         */
        rotation: null,

        /**
         * @cfg {Array} visibleRange
         * Specify the proportion of the axis to be rendered. The series bound to
         * this axis will be synchronized and transformed accordingly.
         */
        visibleRange: [0, 1],

        /**
         * @cfg {Boolean} needHighPrecision
         * Indicates that the axis needs high precision surface implementation.
         * See {@link Ext.draw.engine.Canvas#highPrecision}
         */
        needHighPrecision: false,

        /**
         * @cfg {Ext.chart.axis.Axis|String|Number} linkedTo
         * Axis (itself, its ID or index) that this axis is linked to.
         * When an axis is linked to a master axis, it will use the same data as the master axis.
         * It can be used to show additional info, or to ease reading the chart by duplicating
         * the scales.
         */
        linkedTo: null,

        /**
         * @cfg {Number|Object}
         * If `floating` is a number, then it's a percentage displacement of the axis from its
         * initial {@link #position} in the direction opposite to the axis' direction. For instance,
         * '{position:"left", floating:75}' displays a vertical  axis at 3/4 of the chart, starting
         * from the left. It is equivalent to '{position:"right", floating:25}'. If `floating` is
         * an object, then `floating.value` is the position of this axis along another axis, defined
         * by `floating.alongAxis`, where `alongAxis` is an ID, an
         * {@link Ext.chart.AbstractChart#axes} config index, or the other axis itself. `alongAxis`
         * must have an opposite {@link Ext.chart.axis.Axis#getAlignment alignment}.
         * For example:
         *
         *
         *      axes: [
         *          {
         *              title: 'Average Temperature (F)',
         *              type: 'numeric',
         *              position: 'left',
         *              id: 'temperature-vertical-axis',
         *              minimum: -30,
         *              maximum: 130
         *          },
         *          {
         *              title: 'Month (2013)',
         *              type: 'category',
         *              position: 'bottom',
         *              floating: {
         *                  value: 32,
         *                  alongAxis: 'temperature-vertical-axis'
         *              }
         *          }
         *      ]
         */
        floating: null
    },

    titleOffset: 0,

    spriteAnimationCount: 0,

    boundSeries: [],

    sprites: null,

    surface: null,

    /**
     * @private
     * @property {Array} range
     * The full data range of the axis. Should not be set directly, Clear it to `null`
     * and use `getRange` to update.
     */
    range: null,

    defaultRange: [0, 1],
    rangePadding: 0.5,

    xValues: [],

    yValues: [],

    masterAxis: null,

    applyRotation: function(rotation) {
        var twoPie = Math.PI * 2;

        return (rotation % twoPie + Math.PI) % twoPie - Math.PI;
    },

    updateRotation: function(rotation) {
        var sprites = this.getSprites(),
            position = this.getPosition();

        if (!this.getHidden() && position === 'angular' && sprites[0]) {
            sprites[0].setAttributes({
                baseRotation: rotation
            });
        }
    },

    applyTitle: function(title, oldTitle) {
        var surface;

        if (Ext.isString(title)) {
            title = { text: title };
        }

        if (!oldTitle) {
            oldTitle = Ext.create('sprite.text', title);

            if ((surface = this.getSurface())) {
                surface.add(oldTitle);
            }
        }
        else {
            oldTitle.setAttributes(title);
        }

        return oldTitle;
    },

    getAdjustByMajorUnit: function() {
        return !this.getHidden() && this.callParent();
    },

    applyFloating: function(floating, oldFloating) {
        if (floating === null) {
            floating = {
                value: null,
                alongAxis: null
            };
        }
        else if (Ext.isNumber(floating)) {
            floating = {
                value: floating,
                alongAxis: null
            };
        }

        if (Ext.isObject(floating)) {
            if (oldFloating && oldFloating.alongAxis) {
                delete this.getChart().getAxis(oldFloating.alongAxis).floatingAxes[this.getId()];
            }

            return floating;
        }

        return oldFloating;
    },

    constructor: function(config) {
        var me = this,
            id;

        me.sprites = [];
        me.labels = [];
        // Maps IDs of the axes that float along this axis to their floating values.
        me.floatingAxes = {};

        config = config || {};

        if (config.position === 'angular') {
            config.style = config.style || {};
            config.style.estStepSize = 1;
        }

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

        me.mixins.observable.constructor.apply(me, arguments);
    },

    /**
     * @private
     * @return {String}
     */
    getAlignment: function() {
        switch (this.getPosition()) {
            case 'left':
            case 'right':
                return 'vertical';

            case 'top':
            case 'bottom':
                return 'horizontal';

            case 'radial':
                return 'radial';

            case 'angular':
                return 'angular';
        }
    },

    /**
     * @private
     * @return {String}
     */
    getGridAlignment: function() {
        switch (this.getPosition()) {
            case 'left':
            case 'right':
                return 'horizontal';

            case 'top':
            case 'bottom':
                return 'vertical';

            case 'radial':
                return 'circular';

            case 'angular':
                return 'radial';
        }
    },

    /**
     * @private
     * Get the surface for drawing the series sprites
     */
    getSurface: function() {
        var me = this,
            chart = me.getChart(),
            surface, gridSurface;

        if (chart && !me.surface) {
            surface = me.surface = chart.getSurface(me.getId(), 'axis');
            gridSurface = me.gridSurface = chart.getSurface('main');

            gridSurface.waitFor(surface);
            me.getGrid();

            me.createLimits();
        }

        return me.surface;
    },

    createLimits: function() {
        var me = this,
            chart = me.getChart(),
            axisSprite = me.getSprites()[0],
            gridAlignment = me.getGridAlignment(),
            limits;

        if (me.getLimits() && gridAlignment) {
            gridAlignment = gridAlignment.replace('3d', '');
            me.limits = limits = {
                surface: chart.getSurface('overlay'),
                lines: new Ext.chart.Markers(),
                titles: new Ext.draw.sprite.Instancing()
            };
            limits.lines.setTemplate({ xclass: 'grid.' + gridAlignment });
            limits.lines.getTemplate().setAttributes({ strokeStyle: 'black' }, true);
            limits.surface.add(limits.lines);
            axisSprite.bindMarker(gridAlignment + '-limit-lines', me.limits.lines);

            me.limitTitleTpl = new Ext.draw.sprite.Text();
            limits.titles.setTemplate(me.limitTitleTpl);
            limits.surface.add(limits.titles);
        }
    },

    applyGrid: function(grid) {
        // Returning an empty object here if grid was set to 'true' so that
        // config merging in the theme works properly.
        if (grid === true) {
            return {};
        }

        return grid;
    },

    updateGrid: function(grid) {
        var me = this,
            chart = me.getChart(),
            gridSurface = me.gridSurface,
            axisSprite, gridAlignment, gridSprite;

        if (!chart) {
            me.on({
                chartattached: Ext.bind(me.updateGrid, me, [grid]),
                single: true
            });

            return;
        }

        axisSprite = me.getSprites()[0];
        gridAlignment = me.getGridAlignment();

        if (grid) {
            gridSprite = me.gridSpriteEven;

            if (!gridSprite) {
                gridSprite = me.gridSpriteEven = new Ext.chart.Markers();
                gridSprite.setTemplate({ xclass: 'grid.' + gridAlignment });
                gridSurface.add(gridSprite);
                axisSprite.bindMarker(gridAlignment + '-even', gridSprite);
            }

            if (Ext.isObject(grid)) {
                gridSprite.getTemplate().setAttributes(grid);

                if (Ext.isObject(grid.even)) {
                    gridSprite.getTemplate().setAttributes(grid.even);
                }
            }

            gridSprite = me.gridSpriteOdd;

            if (!gridSprite) {
                gridSprite = me.gridSpriteOdd = new Ext.chart.Markers();
                gridSprite.setTemplate({ xclass: 'grid.' + gridAlignment });
                gridSurface.add(gridSprite);
                axisSprite.bindMarker(gridAlignment + '-odd', gridSprite);
            }

            if (Ext.isObject(grid)) {
                gridSprite.getTemplate().setAttributes(grid);

                if (Ext.isObject(grid.odd)) {
                    gridSprite.getTemplate().setAttributes(grid.odd);
                }
            }
        }
    },

    updateMinorTickSteps: function(minorTickSteps) {
        var me = this,
            sprites = me.getSprites(),
            axisSprite = sprites && sprites[0],
            surface;

        if (axisSprite) {
            axisSprite.setAttributes({
                minorTicks: !!minorTickSteps
            });
            surface = me.getSurface();

            if (!me.isConfiguring && surface) {
                surface.renderFrame();
            }
        }
    },

    /**
     *
     * Mapping data value into coordinate.
     *
     * @param {*} value
     * @param {String} field
     * @param {Number} [idx]
     * @param {Ext.util.MixedCollection} [items]
     * @return {Number}
     */
    getCoordFor: function(value, field, idx, items) {
        return this.getLayout().getCoordFor(value, field, idx, items);
    },

    applyPosition: function(pos) {
        return pos.toLowerCase();
    },

    applyLength: function(length, oldLength) {
        return length > 0 ? length : oldLength;
    },

    applyLabel: function(label, oldLabel) {
        if (!oldLabel) {
            oldLabel = new Ext.draw.sprite.Text({});
        }

        if (label) {
            if (this.limitTitleTpl) {
                this.limitTitleTpl.setAttributes(label);
            }

            oldLabel.setAttributes(label);
        }

        return oldLabel;
    },

    applyLayout: function(layout, oldLayout) {
        layout = Ext.factory(layout, null, oldLayout, 'axisLayout');
        layout.setAxis(this);

        return layout;
    },

    applySegmenter: function(segmenter, oldSegmenter) {
        segmenter = Ext.factory(segmenter, null, oldSegmenter, 'segmenter');
        segmenter.setAxis(this);

        return segmenter;
    },

    updateMinimum: function() {
        this.range = null;
    },

    updateMaximum: function() {
        this.range = null;
    },

    hideLabels: function() {
        this.getSprites()[0].setDirty(true);
        this.setLabel({ hidden: true });
    },

    showLabels: function() {
        this.getSprites()[0].setDirty(true);
        this.setLabel({ hidden: false });
    },

    /**
     * Invokes renderFrame on this axis's surface(s)
     */
    renderFrame: function() {
        this.getSurface().renderFrame();
    },

    updateChart: function(newChart, oldChart) {
        var me = this,
            surface;

        if (oldChart) {
            oldChart.unregister(me);
            oldChart.un('serieschange', me.onSeriesChange, me);
            me.linkAxis();
            me.fireEvent('chartdetached', oldChart, me);
        }

        if (newChart) {
            newChart.on('serieschange', me.onSeriesChange, me);
            me.surface = null;
            surface = me.getSurface();
            me.getLabel().setSurface(surface);
            surface.add(me.getSprites());
            surface.add(me.getTitle());
            newChart.register(me);
            me.fireEvent('chartattached', newChart, me);
        }
    },

    applyBackground: function(background) {
        var rect = Ext.ClassManager.getByAlias('sprite.rect');

        return rect.def.normalize(background);
    },

    /**
     * @protected
     * Invoked when data has changed.
     */
    processData: function() {
        this.getLayout().processData();
        this.range = null;
    },

    getDirection: function() {
        return this.getChart().getDirectionForAxis(this.getPosition());
    },

    isSide: function() {
        var position = this.getPosition();

        return position === 'left' || position === 'right';
    },

    applyFields: function(fields) {
        return Ext.Array.from(fields);
    },

    applyVisibleRange: function(visibleRange, oldVisibleRange) {
        var temp;

        this.getChart();

        // If it is in reversed order swap them
        if (visibleRange[0] > visibleRange[1]) {
            temp = visibleRange[0];

            visibleRange[0] = visibleRange[1];
            visibleRange[0] = temp;
        }

        if (visibleRange[1] === visibleRange[0]) {
            visibleRange[1] += 1 / this.getMaxZoom();
        }

        if (visibleRange[1] > visibleRange[0] + 1) {
            visibleRange[0] = 0;
            visibleRange[1] = 1;
        }
        else if (visibleRange[0] < 0) {
            visibleRange[1] -= visibleRange[0];
            visibleRange[0] = 0;
        }
        else if (visibleRange[1] > 1) {
            visibleRange[0] -= visibleRange[1] - 1;
            visibleRange[1] = 1;
        }

        if (oldVisibleRange && visibleRange[0] === oldVisibleRange[0] &&
            visibleRange[1] === oldVisibleRange[1]) {
            return undefined;
        }

        return visibleRange;
    },

    updateVisibleRange: function(visibleRange) {
        this.fireEvent('visiblerangechange', this, visibleRange);
    },

    onSeriesChange: function(chart) {
        var me = this,
            series = chart.getSeries(),
            boundSeries = [],
            linkedTo, masterAxis, getAxisMethod,
            i, ln;

        if (series) {
            getAxisMethod = 'get' + me.getDirection() + 'Axis';

            for (i = 0, ln = series.length; i < ln; i++) {
                if (this === series[i][getAxisMethod]()) {
                    boundSeries.push(series[i]);
                }
            }
        }

        me.boundSeries = boundSeries;

        linkedTo = me.getLinkedTo();
        masterAxis = !Ext.isEmpty(linkedTo) && chart.getAxis(linkedTo);

        if (masterAxis) {
            me.linkAxis(masterAxis);
        }
        else {
            me.getLayout().processData();
        }
    },

    linkAxis: function(masterAxis) {
        var me = this;

        function link(action, slave, master) {
            master.getLayout()[action]('datachange', 'onDataChange', slave);
            master[action]('rangechange', 'onMasterAxisRangeChange', slave);
        }

        if (me.masterAxis) {
            if (!me.masterAxis.destroyed) {
                link('un', me, me.masterAxis);
            }

            me.masterAxis = null;
        }

        if (masterAxis) {
            if (masterAxis.type !== this.type) {
                Ext.Error.raise("Linked axes must be of the same type.");
            }

            link('on', me, masterAxis);
            me.onDataChange(masterAxis.getLayout().labels);
            me.onMasterAxisRangeChange(masterAxis, masterAxis.range);
            me.setStyle(Ext.apply({}, me.config.style, masterAxis.config.style));
            me.setTitle(Ext.apply({}, me.config.title, masterAxis.config.title));
            me.setLabel(Ext.apply({}, me.config.label, masterAxis.config.label));
            me.masterAxis = masterAxis;
        }
    },

    onDataChange: function(data) {
        this.getLayout().labels = data;
    },

    onMasterAxisRangeChange: function(masterAxis, range) {
        this.range = range;
    },

    applyRange: function(newRange) {
        if (!newRange) {
            return this.dataRange.slice(0);
        }
        else {
            return [
                newRange[0] === null ? this.dataRange[0] : newRange[0],
                newRange[1] === null ? this.dataRange[1] : newRange[1]
            ];
        }
    },

    /**
     * @private
     */
    setBoundSeriesRange: function(range) {
        var boundSeries = this.boundSeries,
            style = {},
            series, i,
            sprites, j,
            ln;

        style['range' + this.getDirection()] = range;

        for (i = 0, ln = boundSeries.length; i < ln; i++) {
            series = boundSeries[i];

            if (series.getHidden() === true) {
                continue;
            }

            sprites = series.getSprites();

            for (j = 0; j < sprites.length; j++) {
                sprites[j].setAttributes(style);
            }
        }
    },

    /**
     * Get the range derived from all the bound series.
     * The range value is cached and returned the next time this method is called.
     * Set `recalculate` to `true` to recalculate the range, if changes to the
     * chart, its components or data are expected to affect the range.
     * @param {Boolean} [recalculate]
     * @return {Number[]}
     */
    getRange: function(recalculate) {
        var me = this,
            range = recalculate ? null : me.range,
            oldRange = me.oldRange,
            minimum, maximum;

        if (!range) {
            if (me.masterAxis) {
                range = me.masterAxis.range;
            }
            else {
                minimum = me.getMinimum();
                maximum = me.getMaximum();

                if (Ext.isNumber(minimum) && Ext.isNumber(maximum)) {
                    range = [minimum, maximum];
                }
                else {
                    range = me.calculateRange();
                }

                me.range = range;
            }
        }

        if (range && (!oldRange || range[0] !== oldRange[0] || range[1] !== oldRange[1])) {
            me.fireEvent('rangechange', me, range, oldRange);
            me.oldRange = range;
        }

        return range;
    },

    isSingleDataPoint: function(range) {
        return (range[0] + this.rangePadding) === 0 && (range[1] - this.rangePadding) === 0;
    },

    calculateRange: function() {
        var me = this,
            boundSeries = me.boundSeries,
            layout = me.getLayout(),
            segmenter = me.getSegmenter(),
            minimum = me.getMinimum(),
            maximum = me.getMaximum(),
            visibleRange = me.getVisibleRange(),
            getRangeMethod = 'get' + me.getDirection() + 'Range',
            expandRangeBy = me.getExpandRangeBy(),
            context, attr, majorTicks,
            series, i, ln, seriesRange,
            range = [NaN, NaN];

        // For each series bound to this axis, ask the series for its min/max values
        // and use them to find the overall min/max.
        for (i = 0, ln = boundSeries.length; i < ln; i++) {
            series = boundSeries[i];

            if (series.getHidden() === true) {
                continue;
            }

            seriesRange = series[getRangeMethod]();

            if (seriesRange) {
                Ext.chart.Util.expandRange(range, seriesRange);
            }
        }

        range = Ext.chart.Util.validateRange(range, me.defaultRange, me.rangePadding);

        // The second condition is there to account for a special case where we only have
        // a single data point, so the effective range of coordinated data is 0 (whatever
        // the actual value of that single data point is, it will be assigned an index of
        // zero, as the first and only data point). Since zero range is invalid, the
        // validateRange function above will expand the range by the value of the rangePadding,
        // which makes further expansion by the value of expandRangeBy unnecessary.
        if (expandRangeBy && (!me.isSingleDataPoint(range))) {
            range[0] -= expandRangeBy;
            range[1] += expandRangeBy;
        }

        if (isFinite(minimum)) {
            range[0] = minimum;
        }

        if (isFinite(maximum)) {
            range[1] = maximum;
        }

        // When series `fullStack` config is used, the values may add up to
        // slightly more than the value of the `fullStackTotal` config
        // because of a precision error.
        range[0] = Ext.Number.correctFloat(range[0]);
        range[1] = Ext.Number.correctFloat(range[1]);

        me.range = range;

        // It's important to call 'me.reconcileRange' after the 'range'
        // has been assigned to avoid circular calls.
        if (me.getReconcileRange()) {
            me.reconcileRange();
        }

        // TODO: Find a better way to do this.
        // TODO: The original design didn't take into account that the range of an axis
        // TODO: will depend not just on the range of the data of the bound series in the
        // TODO: direction of the axis, but also on the range of other axes with the
        // TODO: same direction and on the segmentation of the axis (interval between
        // TODO: major ticks).
        // TODO: While the fist omission was possible to retrofit rather gracefully
        // TODO: by adding the axis.reconcileRange method, the second one is harder to deal with.
        // TODO: The issue is that the resulting axis segmentation, which is a part of
        // TODO: the axis sprite layout has to be known before layout has begun.
        // TODO: Example for the logic below:
        // TODO: If we have a range of data of 0..34.5 the step will be 2 and we
        // TODO: will round up the max to 36 based on that step, but when the range is 0..36,
        // TODO: the step becomes 5, so we have to reconcile the range once again where max
        // TODO: becomes 40.
        if (range[0] !== range[1] && me.getAdjustByMajorUnit() && segmenter.adjustByMajorUnit &&
            !me.getMajorTickSteps()) {
            attr = Ext.Object.chain(me.getSprites()[0].attr);
            attr.min = range[0];
            attr.max = range[1];
            attr.visibleMin = visibleRange[0];
            attr.visibleMax = visibleRange[1];
            context = {
                attr: attr,
                segmenter: segmenter
            };
            layout.calculateLayout(context);
            majorTicks = context.majorTicks;

            if (majorTicks) {
                segmenter.adjustByMajorUnit(majorTicks.step, majorTicks.unit.scale, range);

                attr.min = range[0];
                attr.max = range[1];
                context.majorTicks = null;
                layout.calculateLayout(context);
                majorTicks = context.majorTicks;
                segmenter.adjustByMajorUnit(majorTicks.step, majorTicks.unit.scale, range);
            }
            else if (!me.hasClearRangePending) {
                // Axis hasn't been rendered yet.
                me.hasClearRangePending = true;
                me.getChart().on('layout', 'clearRange', me);
            }
        }

        return range;
    },

    /**
     * @private
     */
    clearRange: function() {
        this.hasClearRangePending = null;
        this.range = null;
    },

    /**
     * Expands the range of the axis
     * based on the range of other axes with the same direction (if any).
     */
    reconcileRange: function() {
        var me = this,
            axes = me.getChart().getAxes(),
            direction = me.getDirection(),
            i, ln, axis, range;

        if (!axes) {
            return;
        }

        for (i = 0, ln = axes.length; i < ln; i++) {
            axis = axes[i];
            range = axis.getRange();

            if (axis === me || axis.getDirection() !== direction || !range ||
                !axis.getReconcileRange()) {
                continue;
            }

            if (range[0] < me.range[0]) {
                me.range[0] = range[0];
            }

            if (range[1] > me.range[1]) {
                me.range[1] = range[1];
            }
        }
    },

    applyStyle: function(style, oldStyle) {
        var cls = Ext.ClassManager.getByAlias('sprite.' + this.seriesType);

        if (cls && cls.def) {
            style = cls.def.normalize(style);
        }

        oldStyle = Ext.apply(oldStyle || {}, style);

        return oldStyle;
    },

    themeOnlyIfConfigured: {
        grid: true
    },

    updateTheme: function(theme) {
        var me = this,
            axisTheme = theme.getAxis(),
            position = me.getPosition(),
            initialConfig = me.getInitialConfig(),
            defaultConfig = me.defaultConfig,
            configs = me.self.getConfigurator().configs,
            genericAxisTheme = axisTheme.defaults,
            specificAxisTheme = axisTheme[position],
            themeOnlyIfConfigured = me.themeOnlyIfConfigured,
            key, value, isObjValue, isUnusedConfig, initialValue, cfg;

        axisTheme = Ext.merge({}, genericAxisTheme, specificAxisTheme);

        for (key in axisTheme) {
            value = axisTheme[key];
            cfg = configs[key];

            if (value !== null && value !== undefined && cfg) {
                initialValue = initialConfig[key];
                isObjValue = Ext.isObject(value);
                isUnusedConfig = initialValue === defaultConfig[key];

                if (isObjValue) {
                    if (isUnusedConfig && themeOnlyIfConfigured[key]) {
                        continue;
                    }

                    value = Ext.merge({}, value, initialValue);
                }

                if (isUnusedConfig || isObjValue) {
                    me[cfg.names.set](value);
                }
            }
        }
    },

    updateCenter: function(center) {
        var me = this,
            sprites = me.getSprites(),
            axisSprite = sprites[0],
            centerX = center[0],
            centerY = center[1];

        if (axisSprite) {
            axisSprite.setAttributes({
                centerX: centerX,
                centerY: centerY
            });
        }

        if (me.gridSpriteEven) {
            me.gridSpriteEven.getTemplate().setAttributes({
                translationX: centerX,
                translationY: centerY,
                rotationCenterX: centerX,
                rotationCenterY: centerY
            });
        }

        if (me.gridSpriteOdd) {
            me.gridSpriteOdd.getTemplate().setAttributes({
                translationX: centerX,
                translationY: centerY,
                rotationCenterX: centerX,
                rotationCenterY: centerY
            });
        }
    },

    getSprites: function() {
        if (!this.getChart()) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            range = me.getRange(),
            position = me.getPosition(),
            chart = me.getChart(),
            animation = chart.getAnimation(),
            length = me.getLength(),
            axisClass = me.superclass,
            mainSprite, style,
            animationModifier;

        // If animation is false, then stop animation.
        if (animation === false) {
            animation = {
                duration: 0
            };
        }

        style = Ext.applyIf({
            position: position,
            axis: me,
            length: length,
            grid: me.getGrid(),
            hidden: me.getHidden(),
            titleOffset: me.titleOffset,
            layout: me.getLayout(),
            segmenter: me.getSegmenter(),
            totalAngle: me.getTotalAngle(),
            label: me.getLabel()
        }, me.getStyle());

        if (range) {
            style.min = range[0];
            style.max = range[1];
        }

        // If the sprites are not created.
        if (!me.sprites.length) {
            while (!axisClass.xtype) {
                axisClass = axisClass.superclass;
            }

            mainSprite = Ext.create('sprite.' + axisClass.xtype, style);
            animationModifier = mainSprite.getAnimation();
            animationModifier.setCustomDurations({
                baseRotation: 0
            });
            animationModifier.on('animationstart', 'onAnimationStart', me);
            animationModifier.on('animationend', 'onAnimationEnd', me);
            mainSprite.setLayout(me.getLayout());
            mainSprite.setSegmenter(me.getSegmenter());
            mainSprite.setLabel(me.getLabel());
            me.sprites.push(mainSprite);
            me.updateTitleSprite();
        }
        else {
            mainSprite = me.sprites[0];
            mainSprite.setAnimation(animation);
            mainSprite.setAttributes(style);
        }

        if (me.getRenderer()) {
            mainSprite.setRenderer(me.getRenderer());
        }

        return me.sprites;
    },

    /**
     * @private
     */
    performLayout: function() {
        if (this.isConfiguring) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            sprites = me.getSprites(),
            surface = me.getSurface(),
            chart = me.getChart(),
            sprite = sprites && sprites[0];

        if (chart && surface && sprite) {
            sprite.callUpdater(null, 'layout'); // recalculate axis ticks
            chart.scheduleLayout();
        }
    },

    updateTitleSprite: function() {
        var me = this,
            length = me.getLength(),
            surface, thickness, title, position, margin, titleMargin, anchor;

        if (!me.sprites[0] || !Ext.isNumber(length)) {
            return;
        }

        thickness = this.sprites[0].thickness;
        surface = me.getSurface();
        title = me.getTitle();
        position = me.getPosition();
        margin = me.getMargin();
        titleMargin = me.getTitleMargin();
        anchor = surface.roundPixel(length / 2);

        if (title) {
            switch (position) {
                case 'top':
                    title.setAttributes({
                        x: anchor,
                        y: margin + titleMargin / 2,
                        textBaseline: 'top',
                        textAlign: 'center'
                    }, true);

                    title.applyTransformations();
                    me.titleOffset = title.getBBox().height + titleMargin;

                    break;

                case 'bottom':
                    title.setAttributes({
                        x: anchor,
                        y: thickness + titleMargin / 2,
                        textBaseline: 'top',
                        textAlign: 'center'
                    }, true);

                    title.applyTransformations();
                    me.titleOffset = title.getBBox().height + titleMargin;

                    break;

                case 'left':
                    title.setAttributes({
                        x: margin + titleMargin / 2,
                        y: anchor,
                        textBaseline: 'top',
                        textAlign: 'center',
                        rotationCenterX: margin + titleMargin / 2,
                        rotationCenterY: anchor,
                        rotationRads: -Math.PI / 2
                    }, true);

                    title.applyTransformations();
                    me.titleOffset = title.getBBox().width + titleMargin;

                    break;

                case 'right':
                    title.setAttributes({
                        x: thickness - margin + titleMargin / 2,
                        y: anchor,
                        textBaseline: 'bottom',
                        textAlign: 'center',
                        rotationCenterX: thickness + titleMargin / 2,
                        rotationCenterY: anchor,
                        rotationRads: Math.PI / 2
                    }, true);

                    title.applyTransformations();
                    me.titleOffset = title.getBBox().width + titleMargin;

                    break;
            }
        }
    },

    onThicknessChanged: function() {
        this.getChart().onThicknessChanged();
    },

    getThickness: function() {
        if (this.getHidden()) {
            return 0;
        }

        return (this.sprites[0] && this.sprites[0].thickness || 1) + this.titleOffset +
                this.getMargin();
    },

    onAnimationStart: function() {
        this.spriteAnimationCount++;

        if (this.spriteAnimationCount === 1) {
            this.fireEvent('animationstart', this);
        }
    },

    onAnimationEnd: function() {
        this.spriteAnimationCount--;

        if (this.spriteAnimationCount === 0) {
            this.fireEvent('animationend', this);
        }
    },

    // Methods used in ComponentQuery and controller
    getItemId: function() {
        return this.getId();
    },

    getAncestorIds: function() {
        return [this.getChart().getId()];
    },

    isXType: function(xtype) {
        return xtype === 'axis';
    },

    // Override the Observable's method to redirect listener scope
    // resolution to the chart.
    resolveListenerScope: function(defaultScope) {
        var me = this,
            namedScope = Ext._namedScopes[defaultScope],
            chart = me.getChart(),
            scope;

        if (!namedScope) {
            scope = chart ? chart.resolveListenerScope(defaultScope, false) : (defaultScope || me);
        }
        else if (namedScope.isThis) {
            scope = me;
        }
        else if (namedScope.isController) {
            scope = chart ? chart.resolveListenerScope(defaultScope, false) : me;
        }
        else if (namedScope.isSelf) {
            scope = chart ? chart.resolveListenerScope(defaultScope, false) : me;

            // Class body listener. No chart controller, nor chart container controller.
            if (scope === chart && !chart.getInheritedConfig('defaultListenerScope')) {
                scope = me;
            }
        }

        return scope;
    },

    destroy: function() {
        var me = this;

        me.setChart(null);
        me.surface.destroy();
        me.surface = null;
        me.callParent();
    }
});

