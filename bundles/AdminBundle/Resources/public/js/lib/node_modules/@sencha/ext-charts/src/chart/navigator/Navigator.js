/**
 * The Navigator component is used to visually set the visible range of the x-axis
 * of a cartesian chart.
 *
 * This component is meant to be used with the Navigator Container
 * via its {@link Ext.chart.navigator.Container#navigator} config.
 *
 * IMPORTANT: even though the Navigator component is a kind of chart, it should not be
 * treated as such. Correct behavior is not guaranteed when using hidden/private configs.
 */
Ext.define('Ext.chart.navigator.Navigator', {
    extend: 'Ext.chart.navigator.NavigatorBase',

    isNavigator: true,

    requires: [
        'Ext.chart.navigator.sprite.RangeMask'
    ],

    config: {
        /**
         * @cfg {'bottom'/'top'} [docked='bottom']
         */
        docked: 'bottom',

        /**
         * @cfg {'series'/'chart'} [span='series']
         * Whether the navigator should span the 'series' (default) or the whole 'chart'.
         */
        span: 'series',

        insetPadding: 0,
        innerPadding: 0,

        /**
         * @cfg {Ext.chart.navigator.Container} navigatorContainer
         * 'parent' is reserved in Modern, 'container' is reserved in Classic,
         * so we use 'navigatorContainer' as a config name.
         * @private
         */
        navigatorContainer: null,

        /**
         * @cfg {String} axis (required)
         * The ID of the {@link #chart chart's} axis to link to.
         * The axis should be positioned to 'bottom' or 'top' in the chart.
         */
        axis: null,

        /**
         * @cfg {Number} [tolerance=20]
         * The maximum horizontal delta between the pointer/finger and the center of a navigator
         * thumb. Used for hit testing.
         */
        tolerance: 20,

        /**
         * @cfg {Number} [minimum=0.8]
         * The start of the visible range, where the visible range is a [0, 1] interval.
         */
        minimum: 0.8,

        /**
         * @cfg {Number} [maximum=1]
         * The end of the visible range, where the visible range is a [0, 1] interval.
         */
        maximum: 1,

        /**
         * @cfg {Number} [thumbGap=30]
         * Minimum gap between navigator thumbs in pixels.
         */
        thumbGap: 30,

        autoHideThumbs: true,

        width: '100%',

        /**
         * @cfg {Number} [height=75]
         * The height of the navigator component.
         */
        height: 75

        /**
         * @cfg flipXY
         * @hide
         */

        /**
         * @cfg series
         * @hide
         */

        /**
         * @cfg axes
         * @hide
         */

        /**
         * @cfg store
         * @hide
         */

        /**
         * @cfg legend
         * @hide
         */

        /**
         * @cfg interactions
         * @hide
         */

        /**
         * @cfg highlightItem
         * @hide
         */

        /**
         * @cfg theme
         * @hide
         */

        /**
         * @cfg innerPadding
         * @hide
         */

        /**
         * @cfg insetPadding
         * @hide
         */
    },

    dragType: null,

    constructor: function(config) {
        var me = this,
            visibleRange, overlay;

        config = config || {};
        visibleRange = [
            config.minimum || 0.8,
            config.maximum || 1
        ];

        me.callParent([config]);

        overlay = me.overlaySurface;
        overlay.element.setStyle({
            zIndex: 100
        });

        me.rangeMask = overlay.add({
            type: 'rangemask',
            min: visibleRange[0],
            max: visibleRange[1],
            fillStyle: 'rgba(0, 0, 0, .25)'
        });

        me.onDragEnd(); // Set 'thumbOpacity' of the range mask sprite to 0, if needed,
        // and apply animation modifier changes after that, so that the attribute is set
        // instantly.
        me.rangeMask.setAnimation({
            duration: 500,
            customDurations: {
                min: 0,
                max: 0,
                translationX: 0,
                translationY: 0,
                scalingX: 0,
                scalingY: 0,
                scalingCenterX: 0,
                scalingCenterY: 0,
                fillStyle: 0,
                strokeStyle: 0
            }
        });

        me.setVisibleRange(visibleRange);
    },

    createSurface: function(id) {
        var surface = this.callParent([id]);

        if (id === 'overlay') {
            this.overlaySurface = surface;
        }

        return surface;
    },

    // Note: 'applyDock' and 'updateDock' won't ever be called in Classic.
    // See Classic NavigatorBase.

    applyAxis: function(axis) {
        return this.getNavigatorContainer().getChart().getAxis(axis);
    },

    updateAxis: function(axis, oldAxis) {
        var me = this,
            eventName = 'visiblerangechange',
            eventHandler = 'onAxisVisibleRangeChange';

        if (oldAxis) {
            oldAxis.un(eventName, eventHandler, me);
        }

        if (axis) {
            axis.on(eventName, eventHandler, me);
        }

        me.axis = axis;
    },

    getAxis: function() {
        // The superclass doesn't have the 'axis' config, but it has the same method,
        // which we override here to act as a getter for the config. The user is not
        // expected to use the original method in this subclass anyway.
        return this.axis;
    },

    onAxisVisibleRangeChange: function(axis, visibleRange) {
        this.setVisibleRange(visibleRange);
    },

    updateNavigatorContainer: function(navigatorContainer) {
        var me = this,
            oldChart = me.chart,
            chart = me.chart = navigatorContainer && navigatorContainer.getChart(),
            chartSeriesList = chart && chart.getSeries(),
            // 'legendStore' already exists in the base class.
            chartLegendStore = me.chartLegendStore,
            navigatorSeriesList = [],
            storeEventName = 'update',
            // 'onLegendStoreUpdate' already exists in the base class.
            storeEventHandler = 'onChartLegendStoreUpdate',
            chartSeries, navigatorSeries,
            seriesConfig, i;

        if (oldChart) {
            oldChart.un('layout', 'afterBoundChartLayout', me);
            oldChart.un('themechange', 'onChartThemeChange', me);
            oldChart.un('storechange', 'onChartStoreChange', me);
        }

        chart.on('layout', 'afterBoundChartLayout', me);

        for (i = 0; i < chartSeriesList.length; i++) {
            chartSeries = chartSeriesList[i];
            seriesConfig = me.getSeriesConfig(chartSeries);
            navigatorSeries = Ext.create('series.' + seriesConfig.type, seriesConfig);
            navigatorSeries.parentSeries = chartSeries;
            chartSeries.navigatorSeries = navigatorSeries;
            navigatorSeriesList.push(navigatorSeries);
        }

        if (chartLegendStore) {
            chartLegendStore.un(storeEventName, storeEventHandler, me);
            me.chartLegendStore = null;
        }

        if (chart) {
            me.setStore(chart.getStore());
            me.chartLegendStore = chartLegendStore = chart.getLegendStore();

            if (chartLegendStore) {
                chartLegendStore.on(storeEventName, storeEventHandler, me);
            }

            chart.on('themechange', 'onChartThemeChange', me);
            chart.on('storechange', 'onChartStoreChange', me);
            me.onChartThemeChange(chart, chart.getTheme());
        }

        me.setSeries(navigatorSeriesList);
    },

    onChartThemeChange: function(chart, theme) {
        this.setTheme(theme);
    },

    onChartStoreChange: function(chart, store) {
        this.setStore(store);
    },

    addCustomStyle: function(config, style, subStyle) {
        var fillStyle, strokeStyle;

        style = style || {};
        subStyle = subStyle || {};

        config.style = config.style || {};
        config.subStyle = config.subStyle || {};

        fillStyle = style && (style.fillStyle || style.fill);
        strokeStyle = style && (style.strokeStyle || style.stroke);

        if (fillStyle) {
            config.style.fillStyle = fillStyle;
        }

        if (strokeStyle) {
            config.style.strokeStyle = strokeStyle;
        }

        fillStyle = subStyle && (subStyle.fillStyle || subStyle.fill);
        strokeStyle = subStyle && (subStyle.strokeStyle || subStyle.stroke);

        if (fillStyle) {
            config.subStyle.fillStyle = fillStyle;
        }

        if (strokeStyle) {
            config.subStyle.strokeStyle = strokeStyle;
        }

        return config;
    },

    getSeriesConfig: function(chartSeries) {
        var me = this,
            style = chartSeries.getStyle(),
            config;

        if (chartSeries.isLine) {
            config = me.addCustomStyle({
                type: 'line',
                fill: true,
                xField: chartSeries.getXField(),
                yField: chartSeries.getYField(),
                smooth: chartSeries.getSmooth()
            }, style);
        }
        else if (chartSeries.isCandleStick) {
            config = me.addCustomStyle({
                type: 'line',
                fill: true,
                xField: chartSeries.getXField(),
                yField: chartSeries.getCloseField()
            }, style.raiseStyle);
        }
        else if (chartSeries.isArea || chartSeries.isBar) {
            config = me.addCustomStyle({
                type: 'area',
                xField: chartSeries.getXField(),
                yField: chartSeries.getYField()
            }, style, chartSeries.getSubStyle());
        }
        else {
            Ext.raise("Navigator only works with 'line', 'bar', 'candlestick' and 'area' series.");
        }

        config.style.fillOpacity = 0.2;

        return config;
    },

    onChartLegendStoreUpdate: function(store, record) {
        var me = this,
            chart = me.chart,
            series;

        if (chart && record) {
            series = chart.getSeries().map[record.get('series')];

            if (series && series.navigatorSeries) {
                series.navigatorSeries.setHiddenByIndex(record.get('index'),
                                                        record.get('disabled'));
                me.redraw();
            }
        }
    },

    setupEvents: function() {
        // Called from NavigatorBase classes.
        var me = this,
            overlayEl = me.overlaySurface.element;

        overlayEl.on({
            scope: me,
            drag: 'onDrag',
            dragstart: 'onDragStart',
            dragend: 'onDragEnd',
            dragcancel: 'onDragEnd',
            mousemove: 'onMouseMove'
        });
    },

    onMouseMove: function(e) {
        var me = this,
            overlayEl = me.overlaySurface.element,
            style = overlayEl.dom.style,
            dragType = me.getDragType(e.pageX - overlayEl.getXY()[0]);

        switch (dragType) {
            case 'min':
            case 'max':
                style.cursor = 'ew-resize';
                break;

            case 'pan':
                style.cursor = 'move';
                break;

            default:
                style.cursor = 'default';
        }
    },

    getDragType: function(x) {
        var me = this,
            t = me.getTolerance(),
            width = me.overlaySurface.element.getSize().width,
            rangeMask = me.rangeMask,
            min = width * rangeMask.attr.min,
            max = width * rangeMask.attr.max,
            dragType;

        if (x > min + t && x < max - t) {
            dragType = 'pan';
        }
        else if (x <= min + t && x > min - t) {
            dragType = 'min';
        }
        else if (x >= max - t && x < max + t) {
            dragType = 'max';
        }

        return dragType;
    },

    onDragStart: function(e) {
        var me = this,
            x, dragType;

        // Limit drags to single touch.
        if (me.dragType || e && e.touches && e.touches.length > 1) {
            return;
        }

        x = e.touches[0].pageX - me.overlaySurface.element.getXY()[0];
        dragType = me.getDragType(x);

        me.rangeMask.attr.thumbOpacity = 1;

        if (dragType) {
            me.dragType = dragType;
            me.touchId = e.touches[0].identifier;
            me.dragX = x;
        }
    },

    onDrag: function(e) {
        if (e.touch.identifier !== this.touchId) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            overlayEl = me.overlaySurface.element,
            width = overlayEl.getSize().width,
            x = e.touches[0].pageX - overlayEl.getXY()[0],
            thumbGap = me.getThumbGap() / width,
            rangeMask = me.rangeMask,
            min = rangeMask.attr.min,
            max = rangeMask.attr.max,
            delta = max - min,
            dragType = me.dragType,
            drag = me.dragX,
            dx = (x - drag) / width;

        if (dragType === 'pan') {
            min += dx;
            max += dx;

            if (min < 0) {
                min = 0;
                max = delta;
            }

            if (max > 1) {
                max = 1;
                min = max - delta;
            }
        }
        else if (dragType === 'min') {
            min += dx;

            if (min < 0) {
                min = 0;
            }

            if (min > max - thumbGap) {
                min = max - thumbGap;
            }
        }
        else if (dragType === 'max') {
            max += dx;

            if (max > 1) {
                max = 1;
            }

            if (max < min + thumbGap) {
                max = min + thumbGap;
            }
        }
        else {
            return;
        }

        me.dragX = x;
        me.setVisibleRange([min, max]);
    },

    onDragEnd: function() {
        var me = this,
            autoHideThumbs = me.getAutoHideThumbs();

        me.dragType = null;

        if (autoHideThumbs) {
            me.rangeMask.setAttributes({
                thumbOpacity: 0
            });
        }
    },

    updateMinimum: function(mininum) {
        if (!this.isConfiguring) {
            this.setVisibleRange([mininum, this.getMaximum()]);
        }
    },

    updateMaximum: function(maximum) {
        if (!this.isConfiguring) {
            this.setVisibleRange([this.getMinimum(), maximum]);
        }
    },

    getMinimum: function() {
        return this.rangeMask.attr.min;
    },

    getMaximum: function() {
        return this.rangeMask.attr.max;
    },

    setVisibleRange: function(visibleRange) {
        var me = this,
            chart = me.chart;

        me.axis.setVisibleRange(visibleRange);
        me.rangeMask.setAttributes({
            min: visibleRange[0],
            max: visibleRange[1]
        });
        me.getSurface('overlay').renderFrame();

        chart.suspendAnimation();
        chart.redraw();
        chart.resumeAnimation();
    },

    afterBoundChartLayout: function() {
        var me = this,
            spanSeries = me.getSpan() === 'series',
            mainRect = me.chart.getMainRect(),
            size = me.element.getSize();

        if (mainRect && spanSeries) {
            me.setInsetPadding({
                left: mainRect[0],
                right: size.width - mainRect[2] - mainRect[0],
                top: 0,
                bottom: 0
            });
            me.performLayout();
        }
    },

    afterChartLayout: function() {
        var me = this,
            size = me.overlaySurface.element.getSize();

        me.rangeMask.setAttributes({
            scalingCenterX: 0,
            scalingCenterY: 0,
            scalingX: size.width,
            scalingY: size.height
        });
    },

    doDestroy: function() {
        var chart = this.chart;

        if (chart && !chart.destroyed) {
            chart.un('layout', 'afterBoundChartLayout', this);
        }

        this.callParent();
    }
});
