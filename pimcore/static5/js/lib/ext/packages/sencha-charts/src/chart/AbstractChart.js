/**
 * The Ext.chart package provides the capability to visualize data.
 * Each chart binds directly to a {@link Ext.data.Store store} enabling automatic updates of the chart.
 * A chart configuration object has some overall styling options as well as an array of axes
 * and series. A chart instance example could look like this:
 *
 *     Ext.create('Ext.chart.CartesianChart', {
 *         width: 800,
 *         height: 600,
 *         animation: {
 *             easing: 'backOut',
 *             duration: 500
 *         },
 *         store: store1,
 *         legend: {
 *             position: 'right'
 *         },
 *         axes: [
 *             // ...some axes options...
 *         ],
 *         series: [
 *             // ...some series options...
 *         ]
 *     });
 *
 * In this example we set the `width` and `height` of a chart; We decide whether our series are
 * animated or not and we select a store to be bound to the chart; We also set the legend to the right part of the
 * chart.
 *
 * You can register certain interactions such as {@link Ext.chart.interactions.PanZoom} on the chart by specify an
 * array of names or more specific config objects. All the events will be wired automatically.
 *
 * You can also listen to series `itemXXX` events on both chart and series level.
 *
 * For example:
 *
 *     Ext.create('Ext.chart.CartesianChart', {
 *         plugins: {
 *             ptype: 'chartitemevents',
 *             moveEvents: true
 *         },
 *         store: {
 *             fields: ['pet', 'households', 'total'],
 *             data: [
 *                 {pet: 'Cats', households: 38, total: 93},
 *                 {pet: 'Dogs', households: 45, total: 79},
 *                 {pet: 'Fish', households: 13, total: 171}
 *             ]
 *         },
 *         axes: [{
 *             type: 'numeric',
 *             position: 'left'
 *         }, {
 *             type: 'category',
 *             position: 'bottom'
 *         }],
 *         series: [{
 *             type: 'bar',
 *             xField: 'pet',
 *             yField: 'households',
 *             listeners: {
 *                 itemmousemove: function (series, item, event) {
 *                     console.log('itemmousemove', item.category, item.field);
 *                 }
 *             }
 *         }, {
 *             type: 'line',
 *             xField: 'pet',
 *             yField: 'total',
 *             marker: true
 *         }],
 *         listeners: { // Listen to itemclick events on all series.
 *             itemclick: function (chart, item, event) {
 *                 console.log('itemclick', item.category, item.field);
 *             }
 *         }
 *     });
 *
 * For more information about the axes and series configurations please check the documentation of
 * each series (Line, Bar, Pie, etc).
 *
 */

Ext.define('Ext.chart.AbstractChart', {

    extend: 'Ext.draw.Container',

    requires: [
        'Ext.chart.theme.Default',
        'Ext.chart.series.Series',
        'Ext.chart.interactions.Abstract',
        'Ext.chart.axis.Axis',
        'Ext.data.StoreManager',
        'Ext.chart.Legend',
        'Ext.data.Store'
    ],

    defaultBindProperty: 'store',

    /**
     * @event beforerefresh
     * Fires before a refresh to the chart data is called.  If the `beforerefresh` handler returns
     * `false` the {@link #refresh} action will be canceled.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event refresh
     * Fires after the chart data has been refreshed.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event redraw
     * Fires after the chart is redrawn.
     * @param {Ext.chart.AbstractChart} this
     */

    /**
     * @event itemmousemove
     * Fires when the mouse is moved on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseup
     * Fires when a mouseup event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmousedown
     * Fires when a mousedown event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseover
     * Fires when the mouse enters a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemmouseout
     * Fires when the mouse exits a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemclick
     * Fires when a click event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemdblclick
     * Fires when a double click event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @event itemtap
     * Fires when a tap event occurs on a series item.
     * *Note*: This event requires the {@link Ext.chart.plugin.ItemEvents chartitemevents}
     * plugin be added to the chart.
     * @param {Ext.chart.AbstractChart} chart
     * @param {Object} item
     * @param {Event} event
     */

    /**
     * @property version Current version of Sencha Charts.
     * @type {String}
     */
    version: '2.5.0',

    config: {

        /**
         * @cfg {Ext.data.Store} store
         * The store that supplies data to this chart.
         */
        store: 'ext-empty-store',

        /**
         * @cfg {String} [theme="default"]
         * The name of the theme to be used. A theme defines the colors and styles
         * used by the series, axes, markers and other chart components.
         * Please see the documentation for the {@link Ext.chart.theme.Base} class for more information.
         * Possible theme values are:
         *   - 'green', 'sky', 'red', 'purple', 'blue', 'yellow'
         *   - 'category1' to 'category6'
         *   - and the above theme names with the '-gradients' suffix, e.g. 'green-gradients'
         */
        theme: 'default',

        /**
         * @cfg {Object} style
         * The style for the chart component.
         */
        style: null,

        /**
         * @cfg {Boolean/Object} shadow (optional) `true` for the default shadow configuration 
         * `{shadowOffsetX: 2, shadowOffsetY: 2, shadowBlur: 3, shadowColor: '#444'}`
         * or a standard shadow config object to be used for default chart shadows.
         */
        shadow: false,

        /**
         * @cfg {Boolean/Object} animation (optional) `true` for the default animation (easing: 'ease' and duration: 500)
         * or a standard animation config object to be used for default chart animations.
         */
        animation: !Ext.isIE8,

        /**
         * @cfg {Ext.chart.series.Series/Array} series
         * Array of {@link Ext.chart.series.Series Series} instances or config objects. For example:
         *
         *     series: [{
         *         type: 'column',
         *         axis: 'left',
         *         listeners: {
         *             'afterrender': function() {
         *                 console.log('afterrender');
         *             }
         *         },
         *         xField: 'category',
         *         yField: 'data1'
         *     }]
         */
        series: [],

        /**
         * @cfg {Ext.chart.axis.Axis/Array/Object} axes
         * Array of {@link Ext.chart.axis.Axis Axis} instances or config objects. For example:
         *
         *     axes: [{
         *         type: 'numeric',
         *         position: 'left',
         *         title: 'Number of Hits',
         *         minimum: 0
         *     }, {
         *         type: 'category',
         *         position: 'bottom',
         *         title: 'Month of the Year'
         *     }]
         */
        axes: [],

        /**
         * @cfg {Ext.chart.Legend/Object} legend
         */
        legend: null,

        /**
         * @cfg {Array} colors Array of colors/gradients to override the color of items and legends.
         */
        colors: null,

        /**
         * @cfg {Object|Number|String} insetPadding The amount of inset padding in pixels for the chart.
         * Inset padding is the padding from the boundary of the chart to any of its contents.
         */
        insetPadding: {
            top: 10,
            left: 10,
            right: 10,
            bottom: 10
        },

        /**
         * @cfg {Object} background Set the chart background. This can be a gradient object, image, or color.
         *
         * For example, if `background` were to be a color we could set the object as
         *
         *     background: '#ccc'
         *
         * You can specify an image by using:
         *
         *     background: {
         *         type: 'image',
         *         src: 'http://path.to.image/'
         *     }
         *
         * Also you can specify a gradient by using the gradient object syntax:
         *
         *     background: {
         *         type: 'linear',
         *         degrees: 0,
         *         stops: [
         *             {
         *                 offset: 0,
         *                 color: 'white'
         *             },
         *             {
         *                 offset: 1,
         *                 color: 'blue'
         *             }
         *         ]
         *     }
         */
        background: null,

        /**
         * @cfg {Array} interactions
         * Interactions are optional modules that can be plugged in to a chart to allow the user to interact
         * with the chart and its data in special ways. The `interactions` config takes an Array of Object
         * configurations, each one corresponding to a particular interaction class identified by a `type` property:
         *
         *     new Ext.chart.AbstractChart({
         *         renderTo: Ext.getBody(),
         *         width: 800,
         *         height: 600,
         *         store: store1,
         *         axes: [
         *             // ...some axes options...
         *         ],
         *         series: [
         *             // ...some series options...
         *         ],
         *         interactions: [{
         *             type: 'interactiontype'
         *             // ...additional configs for the interaction...
         *         }]
         *     });
         *
         * When adding an interaction which uses only its default configuration (no extra properties other than `type`),
         * you can alternately specify only the type as a String rather than the full Object:
         *
         *     interactions: ['reset', 'rotate']
         *
         * The current supported interaction types include:
         *
         * - {@link Ext.chart.interactions.PanZoom panzoom} - allows pan and zoom of axes
         * - {@link Ext.chart.interactions.ItemHighlight itemhighlight} - allows highlighting of series data points
         * - {@link Ext.chart.interactions.ItemInfo iteminfo} - allows displaying details of a data point in a popup panel
         * - {@link Ext.chart.interactions.Rotate rotate} - allows rotation of pie and radar series
         *
         * See the documentation for each of those interaction classes to see how they can be configured.
         *
         * Additional custom interactions can be registered using `'interactions.'` alias prefix.
         */
        interactions: [],

        /**
         * @private
         * The main area of the chart where grid and series are drawn.
         */
        mainRect: null,

        /**
         * @private
         * Override value.
         */
        resizeHandler: null,

        /**
         * @readonly
         * @cfg {Object} highlightItem
         * The current highlight item in the chart.
         * The object must be the one that you get from item events.
         *
         * Note that series can also own highlight items.
         * This notion is separate from this one and should not be used at the same time.
         */
        highlightItem: null
    },

    /**
     * @private
     */
    resizing: 0,

    /**
     * Toggle for chart interactions that require animation to be suspended.
     * @private
     */
    animationSuspended: 0,

    /**
     * @private The z-indexes to use for the various surfaces
     */
    surfaceZIndexes: {
        background: 0,
        main: 1,
        grid: 2,
        series: 3,
        axis: 4,
        chart: 5,
        overlay: 6,
        events: 7
    },

    animating: 0,

    layoutSuspended: 0,

    applyAnimation: function (newAnimation, oldAnimation) {
        if (!newAnimation) {
            newAnimation = {
                duration: 0
            };
        } else if (newAnimation === true) {
            newAnimation = {
                easing: 'easeInOut',
                duration: 500
            };
        }
        return oldAnimation ? Ext.apply({}, newAnimation, oldAnimation) : newAnimation;
    },

    applyInsetPadding: function (padding, oldPadding) {
        if (!Ext.isObject(padding)) {
            return Ext.util.Format.parseBox(padding);
        } else if (!oldPadding) {
            return padding;
        } else {
            return Ext.apply(oldPadding, padding);
        }
    },

    suspendAnimation: function () {
        this.animationSuspended++;
        if (this.animationSuspended === 1) {
            var series = this.getSeries(), i = -1, n = series.length;
            while (++i < n) {
                //update animation config to not animate
                series[i].setAnimation(this.getAnimation());
            }
        }
    },

    resumeAnimation: function () {
        this.animationSuspended--;
        if (this.animationSuspended === 0) {
            var series = this.getSeries(), i = -1, n = series.length;
            while (++i < n) {
                //update animation config to animate
                series[i].setAnimation(this.getAnimation());
            }
        }
    },

    suspendChartLayout: function () {
        this.layoutSuspended++;
        if (this.layoutSuspended === 1) {
            if (this.scheduledLayoutId) {
                this.layoutInSuspension = true;
                this.cancelLayout();
            } else {
                this.layoutInSuspension = false;
            }
        }
    },

    resumeChartLayout: function () {
        this.layoutSuspended--;
        if (this.layoutSuspended === 0) {
            if (this.layoutInSuspension) {
                this.scheduleLayout();
            }
        }
    },

    /**
     * Cancel a scheduled layout.
     */
    cancelLayout: function () {
        if (this.scheduledLayoutId) {
            Ext.draw.Animator.cancel(this.scheduledLayoutId);
            this.scheduledLayoutId = null;
        }
    },

    /**
     * Schedule a layout at next frame.
     */
    scheduleLayout: function () {
        var me = this;

        if (me.rendered && !me.scheduledLayoutId) {
            me.scheduledLayoutId = Ext.draw.Animator.schedule('doScheduleLayout', me);
        }
    },

    doScheduleLayout: function () {
        if (this.layoutSuspended) {
            this.layoutInSuspension = true;
        } else {
            this.performLayout();
        }
    },

    getAnimation: function () {
        // This prevents series from animating into view on chart's first render.
        // Unless series have their own animation config.
        if (this.resizing || this.animationSuspended) {
            return {
                duration: 0
            };
        } else {
            return this.callParent();
        }
    },

    constructor: function (config) {
        var me = this;

        me.itemListeners = {};
        me.surfaceMap = {};

        me.isInitializing = true;
        me.suspendChartLayout();
        me.callParent(arguments);
        delete me.isInitializing;

        me.getSurface('main');
        me.getSurface('chart').setFlipRtlText(me.getInherited().rtl);
        me.getSurface('overlay').waitFor(me.getSurface('series'));
        me.resumeChartLayout();
    },

    applySprites: function (sprites) {
        var surface = this.getSurface('chart');

        sprites = Ext.Array.from(sprites);
        surface.removeAll(true);
        surface.add(sprites);
    },

    initItems: function () {
        var items = this.items,
            i, ln, item;
        if (items && !items.isMixedCollection) {
            this.items = [];
            items = Ext.Array.from(items);
            for (i = 0, ln = items.length; i < ln; i++) {
                item = items[i];
                if (item.type) {
                    Ext.Error.raise("To add custom sprites to the chart use the 'sprites' config.");
                } else {
                    this.items.push(item);
                }
            }
        }
        // @noOptimize.callParent
        this.callParent();
        // noOptimize is needed because in the ext build we have a parent method to call,
        // but in touch we do not so we need to suppress the cmd warning during optimized build
    },

    applyBackground: function (newBackground, oldBackground) {
        var surface = this.getSurface('background'),
            width, height, isUpdateOld;
        if (newBackground) {
            if (oldBackground) {
                width = oldBackground.attr.width;
                height = oldBackground.attr.height;
                isUpdateOld = oldBackground.type === (newBackground.type || 'rect');
            }
            if (newBackground.isSprite) {
                oldBackground = newBackground;
            } else if (newBackground.type === 'image' && Ext.isString(newBackground.src)) {
                if (isUpdateOld) {
                    oldBackground.setAttributes({
                        src: newBackground.src
                    });
                } else {
                    surface.remove(oldBackground, true);
                    oldBackground = surface.add(newBackground);
                }
            } else {
                if (isUpdateOld) {
                    oldBackground.setAttributes({
                        fillStyle: newBackground
                    });
                } else {
                    surface.remove(oldBackground, true);
                    oldBackground = surface.add({
                        type: 'rect',
                        fillStyle: newBackground,
                        fx: {
                            customDurations: {
                                x: 0,
                                y: 0,
                                width: 0,
                                height: 0
                            }
                        }
                    });
                }
            }
        }
        if (width && height) {
            oldBackground.setAttributes({
                width: width,
                height: height
            });
        }
        oldBackground.fx.setConfig(this.getAnimation());
        return oldBackground;
    },

    /**
     * Return the legend store that contains all the legend information.
     * This information is collected from all the series.
     * @return {Ext.data.Store}
     */
    getLegendStore: function () {
        return this.legendStore;
    },

    refreshLegendStore: function () {
        if (this.getLegendStore()) {
            var i, ln,
                series = this.getSeries(), seriesItem,
                legendData = [];
            if (series) {
                for (i = 0, ln = series.length; i < ln; i++) {
                    seriesItem = series[i];
                    if (seriesItem.getShowInLegend()) {
                        seriesItem.provideLegendInfo(legendData);
                    }
                }
            }
            this.getLegendStore().setData(legendData);
        }
    },

    resetLegendStore: function () {
        if (this.getLegendStore()) {
            var data = this.getLegendStore().getData().items,
                i, ln = data.length,
                record;
            for (i = 0; i < ln; i++) {
                record = data[i];
                record.beginEdit();
                record.set('disabled', false);
                record.commit();
            }
        }
    },

    onUpdateLegendStore: function (store, record) {
        var series = this.getSeries(), seriesItem;
        if (record && series) {
            seriesItem = series.map[record.get('series')];
            if (seriesItem) {
                seriesItem.setHiddenByIndex(record.get('index'), record.get('disabled'));
                this.redraw();
            }
        }
    },

    resizeHandler: function (size) {
        this.scheduleLayout();
        return false;
    },

    applyMainRect: function (newRect, rect) {
        if (!rect) {
            return newRect;
        }
        this.getSeries();
        this.getAxes();
        if (newRect[0] === rect[0] &&
            newRect[1] === rect[1] &&
            newRect[2] === rect[2] &&
            newRect[3] === rect[3]) {
            return rect;
        } else {
            return newRect;
        }
    },

    getAxis: function (axis) {
        if (axis instanceof Ext.chart.axis.Axis) {
            return axis;
        } else if (Ext.isNumber(axis)) {
            return this.getAxes()[axis];
        } else if (Ext.isString(axis)) {
            return Ext.ComponentMgr.get(axis);
        } else {
            return null;
        }
    },

    getSurface: function (name, type) {
        name = name || 'main';
        type = type || name;
        var me = this,
            surface = this.callParent([name]),
            zIndexes = me.surfaceZIndexes;
        if (type in zIndexes) {
            surface.element.setStyle('zIndex', zIndexes[type]);
        }
        if (!me.surfaceMap[type]) {
            me.surfaceMap[type] = [];
        }
        if (Ext.Array.indexOf(me.surfaceMap[type], (surface)) < 0) {
            surface.type = type;
            me.surfaceMap[type].push(surface);
        }
        return surface;
    },

    applyAxes: function (newAxes, oldAxes) {
        this.resizing++;

        this.getStore();
        if (!oldAxes) {
            oldAxes = [];
            oldAxes.map = {};
        }
        var result = [], i, ln, axis, oldAxis, linkedTo, id,
            positions = {left: 'right', right: 'left'},
            oldMap = oldAxes.map;
        result.map = {};
        newAxes = Ext.Array.from(newAxes, true);
        for (i = 0, ln = newAxes.length; i < ln; i++) {
            axis = Ext.Object.chain(newAxes[i]);
            if (!axis) {
                continue;
            }

            linkedTo = axis.linkedTo;
            id = axis.id;
            if (Ext.isNumber(linkedTo)) {
                axis = Ext.merge({}, newAxes[linkedTo], axis);
            } else if (Ext.isString(linkedTo)) {
                Ext.Array.each(newAxes, function (item) {
                    if (item.id === axis.linkedTo) {
                        axis = Ext.merge({}, item, axis);
                        return false;
                    }
                });
            }
            axis.id = id;

            if (this.getInherited().rtl) {
                axis.position = positions[axis.position] || axis.position;
            }
            id = axis.getId && axis.getId() || axis.id;
            axis = Ext.factory(axis, null, oldAxis = oldMap[id], 'axis');
            if (axis) {
                axis.setChart(this);
                result.push(axis);
                result.map[axis.getId()] = axis;
                if (!oldAxis) {
                    axis.on('animationstart', 'onAnimationStart', this);
                    axis.on('animationend', 'onAnimationEnd', this);
                }
            }
        }

        for (i in oldMap) {
            if (!result.map[i]) {
                oldMap[i].destroy();
            }
        }

        this.resizing--;

        return result;
    },

    updateAxes: function (newAxes) {
        this.scheduleLayout();
    },

    circularCopyArray: function(inArray, startIndex, count) {
        var outArray = [],
            i, len = inArray && inArray.length;
        if (len) {
            for (i = 0; i < count; i++) {
                outArray.push(inArray[(startIndex + i) % len]);
            }
        }
        return outArray;
    },

    circularCopyObject: function(inObject, startIndex, count) {
        var me = this,
            name, value, outObject = {};
        if (count) {
            for (name in inObject) {
                if (inObject.hasOwnProperty(name)) {
                    value = inObject[name];
                    if (Ext.isArray(value)) {
                        outObject[name] = me.circularCopyArray(value, startIndex, count);
                    } else {
                        outObject[name] = value;
                    }
                }
            }
        }
        return outObject;
    },

    getColors: function () {
        var me = this,
            configColors = me.config.colors,
            theme = me.getTheme();
        if (Ext.isArray(configColors) && configColors.length > 0) {
            configColors = me.applyColors(configColors);
        }
        return configColors || (theme && theme.getColors());
    },

    applyColors: function (newColors) {
        newColors = Ext.Array.map(newColors, function(color) {
            if (Ext.isString(color)) {
                return color;
            } else {
                return color.toString();
            }
        });
        return newColors;
    },

    updateColors: function (newColors) {
        var me = this,
            theme = me.getTheme(),
            colors = newColors || (theme && theme.getColors()),
            colorCount = colors.length,
            colorIndex = 0,
            series = me.getSeries(),
            seriesCount = series && series.length,
            i, seriesItem, seriesColors, seriesColorCount;

        if (colorCount) {
            for (i = 0; i < seriesCount; i++) {
                seriesItem = series[i];
                seriesColorCount = seriesItem.themeColorCount();
                seriesColors = me.circularCopyArray(colors, colorIndex, seriesColorCount);
                colorIndex += seriesColorCount;
                seriesItem.updateChartColors(seriesColors);
            }
        }
        me.refreshLegendStore();
    },

    applyTheme: function (theme) {
        if (theme && theme.isTheme) {
            return theme;
        }
        return Ext.Factory.chartTheme(theme);
    },

    updateTheme: function (theme) {
        var me = this,
            axes = me.getAxes(),
            series = me.getSeries(),
            colors = me.getColors(),
            seriesItem, seriesTheme,
            colorIndex = 0,
            markerIndex = 0,
            markerCount,
            colorCount,
            i;

        me.updateChartTheme(theme);

        for (i = 0; i < axes.length; i++) {
            axes[i].updateTheme(theme);
        }

        for (i = 0; i < series.length; i++) {
            series[i].updateTheme(theme);

            seriesItem = series[i];
            seriesTheme = {};

            if (theme.getSeriesThemes) {
                colorCount = seriesItem.themeColorCount();
                seriesTheme.subStyle = me.circularCopyObject(theme.getSeriesThemes(), colorIndex, colorCount);
                colorIndex += colorCount;
            } else {
                seriesTheme.subStyle = {};
            }

            if (theme.getMarkerThemes) {
                markerCount = seriesItem.themeMarkerCount();
                seriesTheme.markerSubStyle = me.circularCopyObject(theme.getMarkerThemes(), markerIndex, markerCount);
                markerIndex += markerCount;
            } else {
                seriesTheme.markerSubStyle = {};
            }
        }

        me.updateSpriteTheme(theme);

        me.updateColors(colors);
    },

    themeOnlyIfConfigured: {
    },

    updateChartTheme: function (theme) {
        var me = this,
            chartTheme = theme.getChart(),
            initialConfig = me.getInitialConfig(),
            defaultConfig = me.defaultConfig,
            configs = me.getConfigurator().configs,
            genericChartTheme = chartTheme.defaults,
            specificChartTheme = chartTheme[me.xtype],
            themeOnlyIfConfigured = me.themeOnlyIfConfigured,
            key, value, isObjValue, isUnusedConfig, initialValue, cfg;

        chartTheme = Ext.merge({}, genericChartTheme, specificChartTheme);
        for (key in chartTheme) {
            value = chartTheme[key];
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

    updateSpriteTheme: function (theme) {
        var me = this,
            chartSurface = me.getSurface('chart'),
            sprites = chartSurface.getItems(),
            styles = theme.getSprites(),
            sprite, style,
            key, attr,
            isText,
            i, ln;

        for (i = 0, ln = sprites.length; i < ln; i++) {
            sprite = sprites[i];
            style = styles[sprite.type];
            if (style) {
                attr = {};
                isText = sprite.type === 'text';
                for (key in style) {
                    if (!(key in sprite.config)) {
                        // Setting individual font attributes will take over the 'font' shorthand
                        // attribute, but this behavior is undesireable for theming.
                        if (!(isText && key.indexOf('font') === 0 && sprite.config.font)) {
                            attr[key] = style[key];
                        }
                    }
                }
                sprite.setAttributes(attr);
            }
        }
    },

    applySeries: function (newSeries, oldSeries) {
        var me = this,
            result = [],
            oldMap, oldSeriesItem,
            i, ln, series;

        me.resizing++;

        me.getAxes();
        if (!oldSeries) {
            oldSeries = [];
            oldMap = oldSeries.map = {};
        }
        result.map = {};
        newSeries = Ext.Array.from(newSeries, true);
        for (i = 0, ln = newSeries.length; i < ln; i++) {
            series = newSeries[i];
            if (!series) {
                continue;
            }
            oldSeriesItem = oldSeries.map[series.getId && series.getId() || series.id];
            if (series instanceof Ext.chart.series.Series) {
                if (oldSeriesItem !== series) {
                    // Replacing
                    if (oldSeriesItem) {
                        oldSeriesItem.destroy();
                    }
                }
                series.setChart(me);
            } else if (Ext.isObject(series)) {
                if (oldSeriesItem) {
                    // Update
                    oldSeriesItem.setConfig(series);
                    series = oldSeriesItem;
                } else {
                    // Create a series.
                    if (Ext.isString(series)) {
                        series = Ext.create(series.xclass || ('series.' + series), {chart: me});
                    } else {
                        series.chart = me;
                        series = Ext.create(series.xclass || ('series.' + series.type), series);
                    }
                    series.on('animationstart', 'onAnimationStart', me);
                    series.on('animationend', 'onAnimationEnd', me);
                }
            }

            result.push(series);
            result.map[series.getId()] = series;
        }

        for (i in oldMap) {
            if (!result.map[oldMap[i].getId()]) {
                oldMap[i].destroy();
            }
        }

        me.resizing--;

        return result;
    },

    applyLegend: function (newLegend, oldLegend) {
        return Ext.factory(newLegend, Ext.chart.Legend, oldLegend);
    },

    updateLegend: function (legend, oldLegend) {
        if (oldLegend) {
            oldLegend.destroy();
        }
        if (legend) {
            this.getItems();
            this.legendStore = new Ext.data.Store({
                autoDestroy: true,
                fields: [
                    'id', 'name', 'mark', 'disabled', 'series', 'index'
                ]
            });
            legend.setStore(this.legendStore);
            this.refreshLegendStore();
            this.legendStore.on('update', 'onUpdateLegendStore', this);
        }
    },

    updateSeries: function (newSeries, oldSeries) {
        this.resizing++;

        this.fireEvent('serieschange', this, newSeries, oldSeries);
        this.refreshLegendStore();
        this.scheduleLayout();

        this.resizing--;
    },

    applyInteractions: function (interactions, oldInteractions) {
        if (!oldInteractions) {
            oldInteractions = [];
            oldInteractions.map = {};
        }
        var me = this,
            result = [], oldMap = oldInteractions.map,
            i, ln, interaction;
        result.map = {};
        interactions = Ext.Array.from(interactions, true);
        for (i = 0, ln = interactions.length; i < ln; i++) {
            interaction = interactions[i];
            if (!interaction) {
                continue;
            }
            interaction = Ext.factory(interaction, null, oldMap[interaction.getId && interaction.getId() || interaction.id], 'interaction');
            if (interaction) {
                interaction.setChart(me);
                result.push(interaction);
                result.map[interaction.getId()] = interaction;
            }
        }

        for (i in oldMap) {
            if (!result.map[oldMap[i]]) {
                oldMap[i].destroy();
            }
        }
        return result;
    },

    applyStore: function (store) {
        return store && Ext.StoreManager.lookup(store);
    },

    updateStore: function (newStore, oldStore) {
        var me = this;
        if (oldStore) {
            oldStore.un({
                datachanged: 'onDataChanged',
                update: 'onDataChanged',
                scope: me,
                order: 'after'
            });
            if (oldStore.autoDestroy) {
                oldStore.destroy();
            }
        }
        if (newStore) {
            newStore.on({
                datachanged: 'onDataChanged',
                update: 'onDataChanged',
                scope: me,
                order: 'after'
            });
        }

        me.fireEvent('storechange', newStore, oldStore);
        me.onDataChanged();
    },

    /**
     * Redraw the chart. If animations are set this will animate the chart too.
     */
    redraw: function () {
        this.fireEvent('redraw', this);
    },

    performLayout: function () {
        var me = this,
            size = me.innerElement.getSize(),
            chartRect = [0, 0, size.width, size.height],
            background = me.getBackground();

        me.hasFirstLayout = true;
        me.fireEvent('layout');
        me.cancelLayout();
        me.getSurface('background').setRect(chartRect);
        me.getSurface('chart').setRect(chartRect);
        background.setAttributes({
            width: size.width,
            height: size.height
        });
    },

    // Converts page coordinates into chart's 'main' surface coordinates.
    getEventXY: function (e) {
        return this.getSurface().getEventXY(e);
    },

    /**
     * Given an x/y point relative to the chart, find and return the first series item that
     * matches that point.
     * @param {Number} x
     * @param {Number} y
     * @return {Object} An object with `series` and `item` properties, or `false` if no item found.
     */
    getItemForPoint: function (x, y) {
        var me = this,
            seriesList = me.getSeries(),
            mainRect = me.getMainRect(),
            ln = seriesList.length,
            // If we haven't drawn yet, don't attempt to find any items.
            i = me.hasFirstLayout ? ln - 1 : -1,
            series, item;

        // The x,y here are already converted to the 'main' surface coordinates.
        // Series surface rect matches the main surface rect.
        if (!(mainRect && x >= 0 && x <= mainRect[2] && y >= 0 && y <= mainRect[3])) {
            return null;
        }
        // Iterate from the end so that the series that are drawn later get hit tested first.
        for (; i >= 0; i--) {
            series = seriesList[i];
            item = series.getItemForPoint(x, y);
            if (item) {
                return item;
            }
        }

        return null;
    },

    /**
     * Given an x/y point relative to the chart, find and return all series items that match that point.
     * @param {Number} x
     * @param {Number} y
     * @return {Array} An array of objects with `series` and `item` properties.
     */
    getItemsForPoint: function (x, y) {
        var me = this,
            seriesList = me.getSeries(),
            ln = seriesList.length,
            // If we haven't drawn yet, don't attempt to find any items.
            i = me.hasFirstLayout ? ln - 1 : -1,
            items = [],
            series, item;

        // Iterate from the end so that the series that are drawn later get hit tested first.
        for (; i >= 0; i--) {
            series = seriesList[i];
            item = series.getItemForPoint(x, y);
            if (item) {
                items.push(item);
            }
        }

        return items;
    },

    /**
     * @private
     */
    delayThicknessChanged: 0,

    /**
     * @private
     */
    thicknessChanged: false,

    /**
     * Suspend the layout initialized by thickness change
     */
    suspendThicknessChanged: function () {
        this.delayThicknessChanged++;
    },

    /**
     * Resume the layout initialized by thickness change
     */
    resumeThicknessChanged: function () {
        if (this.delayThicknessChanged > 0) {
            this.delayThicknessChanged--;
            if (this.delayThicknessChanged === 0 && this.thicknessChanged) {
                this.onThicknessChanged();
            }
        }
    },

    onAnimationStart: function () {
        this.fireEvent('animationstart', this);
    },

    onAnimationEnd: function () {
        this.fireEvent('animationend', this);
    },

    onThicknessChanged: function () {
        if (this.delayThicknessChanged === 0) {
            this.thicknessChanged = false;
            this.performLayout();
        } else {
            this.thicknessChanged = true;
        }
    },

    /**
     * @private
     */
    onDataChanged: function () {
        var me = this;
        if (me.isInitializing) {
            return;
        }
        var rect = me.getMainRect(),
            store = me.getStore(),
            series = me.getSeries(),
            axes = me.getAxes(),
            colors = me.getColors(),
            i, ln;

        if (!store || !axes || !series) {
            return;
        }
        if (!rect) { // The chart hasn't been rendered yet.
            me.on({
                redraw: me.onDataChanged,
                scope: me,
                single: true
            });
            return;
        }
        for (i = 0, ln = series.length; i < ln; i++) {
            series[i].processData();
        }
        me.updateColors(colors);
        me.redraw();
    },

    /**
     * Changes the data store bound to this chart and refreshes it.
     * @param {Ext.data.Store} store The store to bind to this chart.
     */
    bindStore: function (store) {
        this.setStore(store);
    },

    applyHighlightItem: function (newHighlightItem, oldHighlightItem) {
        if (newHighlightItem === oldHighlightItem) {
            return;
        }
        if (Ext.isObject(newHighlightItem) && Ext.isObject(oldHighlightItem)) {
            if (newHighlightItem.sprite === oldHighlightItem.sprite &&
                newHighlightItem.index === oldHighlightItem.index
                ) {
                return;
            }
        }
        return newHighlightItem;
    },

    updateHighlightItem: function (newHighlightItem, oldHighlightItem) {
        if (oldHighlightItem) {
            oldHighlightItem.series.setAttributesForItem(oldHighlightItem, {highlighted: false});
        }
        if (newHighlightItem) {
            newHighlightItem.series.setAttributesForItem(newHighlightItem, {highlighted: true});
            this.fireEvent('itemhighlight', newHighlightItem);
        }
    },

    // @private remove gently.
    destroy: function () {
        var me = this,
            emptyArray = [],
            legend = me.getLegend();
        me.surfaceMap = null;
        me.setHighlightItem(null);
        me.setSeries(emptyArray);
        me.setAxes(emptyArray);
        me.setInteractions(emptyArray);
        if (legend) {
            legend.destroy();
            me.setLegend(null);
        }
        me.legendStore = null;
        me.setStore(null);
        me.cancelLayout();
        this.callParent(arguments);
    },

    /* ---------------------------------
     Methods needed for ComponentQuery
     ----------------------------------*/

    /**
     * @private
     * @param {Boolean} deep
     * @return {Array}
     */
    getRefItems: function (deep) {
        var me = this,
            series = me.getSeries(),
            axes = me.getAxes(),
            interaction = me.getInteractions(),
            ans = [], i, ln;

        for (i = 0, ln = series.length; i < ln; i++) {
            ans.push(series[i]);
            if (series[i].getRefItems) {
                ans.push.apply(ans, series[i].getRefItems(deep));
            }
        }

        for (i = 0, ln = axes.length; i < ln; i++) {
            ans.push(axes[i]);
            if (axes[i].getRefItems) {
                ans.push.apply(ans, axes[i].getRefItems(deep));
            }
        }

        for (i = 0, ln = interaction.length; i < ln; i++) {
            ans.push(interaction[i]);
            if (interaction[i].getRefItems) {
                ans.push.apply(ans, interaction[i].getRefItems(deep));
            }
        }

        return ans;
    }

});
