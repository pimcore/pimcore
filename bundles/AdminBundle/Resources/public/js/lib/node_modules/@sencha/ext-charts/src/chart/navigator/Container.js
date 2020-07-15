/**
 * The Navigator Container is a component used to lay out the chart and its
 * {@link Ext.chart.navigator.Navigator navigator}, where the navigator is docked
 * to the top/bottom, and the chart fills the rest of the container's space.
 *
 * For example:
 *
 *     @example
 *     Ext.create({
 *         xtype: 'chartnavigator',
 *         renderTo: Ext.getBody(),
 *         width: 600,
 *         height: 400,
 *
 *         chart: {
 *             xtype: 'cartesian',
 *
 *             store: {
 *                 data: (function () {
 *                     var data = [];
 *                     for (var i = 0; i < 360; i++) {
 *                         data.push({
 *                             x: i,
 *                             y: Math.sin(i / 45 * Math.PI)
 *                         });
 *                     }
 *                     return data;
 *                 })()
 *             },
 *             axes: [
 *                 {
 *                     id: 'navigable-axis',
 *
 *                     type: 'numeric',
 *                     position: 'bottom'
 *                 },
 *                 {
 *                     type: 'numeric',
 *                     position: 'left'
 *                 }
 *             ],
 *             series: {
 *                 type: 'line',
 *                 xField: 'x',
 *                 yField: 'y'
 *             }
 *         },
 *
 *         navigator: {
 *             axis: 'navigable-axis'
 *         }
 *     });
 *
 */
Ext.define('Ext.chart.navigator.Container', {
    // We are interested in the docking functionality that's available in
    // the Container in Modern and in the Panel in Classic.
    extend: 'Ext.chart.navigator.ContainerBase',

    requires: [
        'Ext.chart.CartesianChart',
        'Ext.chart.navigator.Navigator'
    ],

    xtype: 'chartnavigator',

    config: {
        /**
         * @cfg {Ext.chart.CartesianChart} chart
         * The chart to make navigable.
         */
        chart: null,

        /**
         * @cfg {Ext.chart.navigator.Navigator} navigator
         */
        navigator: {}
    },

    layout: 'fit',

    applyChart: function(chart, oldChart) {
        if (oldChart) {
            oldChart.destroy();
        }

        if (chart) {
            if (chart.isCartesian) {
                Ext.raise('Only cartesian charts are supported.');
            }

            if (!chart.isChart) {
                chart.$initParent = this;
                chart = new Ext.chart.CartesianChart(chart);
                delete chart.$initParent;
            }
        }

        return chart;
    },

    legendStore: null,
    surfaceRects: null,

    updateChart: function(chart, oldChart) {
        var me = this;

        if (chart) {
            me.legendStore = chart.getLegendStore();

            if (!me.items && me.initItems) {
                me.initItems();
            }

            me.add(chart);
        }
    },

    applyNavigator: function(navigator, oldNavigator) {
        var instance;

        if (oldNavigator) {
            oldNavigator.destroy();
        }

        if (navigator) {
            navigator.navigatorContainer = navigator.parent = this;
            instance = new Ext.chart.navigator.Navigator(navigator);
        }

        return instance;
    },

    preview: function() {
        this.getNavigator().preview(this.getImage());
    },

    download: function(config) {
        config = config || {};
        config.data = this.getImage().data;

        this.getNavigator().download(config);
    },

    setVisibleRange: function(visibleRange) {
        this.getNavigator().setVisibleRange(visibleRange);
    },

    getImage: function(format) {
        var me = this,
            chart = me.getChart(),
            navigator = me.getNavigator(),
            docked = navigator.getDocked(),
            chartImageSize = chart.bodyElement.getSize(),
            navigatorImageSize = navigator.bodyElement.getSize(),
            chartSurfaces = chart.getSurfaces(true),
            navigatorSurfaces = navigator.getSurfaces(true),
            size = {
                width: chartImageSize.width,
                height: chartImageSize.height + navigatorImageSize.height
            },
            image, imageElement,
            surfaces, surface;

        if (docked === 'top') {
            me.shiftSurfaces(chartSurfaces, 0, navigatorImageSize.height);
        }
        else {
            me.shiftSurfaces(navigatorSurfaces, 0, chartImageSize.height);
        }

        surfaces = chartSurfaces.concat(navigatorSurfaces);
        surface = surfaces[0];

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

        me.unshiftSurfaces(surfaces);

        return image;
    },

    shiftSurfaces: function(surfaces, x, y) {
        var ln = surfaces.length,
            i = 0,
            surface;

        this.surfaceRects = {};

        for (; i < ln; i++) {
            surface = surfaces[i];
            this.shiftSurface(surface, x, y);
        }
    },

    shiftSurface: function(surface, x, y) {
        var rect = surface.getRect();

        this.surfaceRects[surface.getId()] = rect.slice();

        rect[0] += x;
        rect[1] += y;
    },

    unshiftSurfaces: function(surfaces) {
        var rects = this.surfaceRects,
            ln = surfaces.length,
            i = 0,
            surface, rect, oldRect;

        if (rects) {
            for (; i < ln; i++) {
                surface = surfaces[i];
                rect = surface.getRect();
                oldRect = rects[surface.getId()];

                if (oldRect) {
                    rect[0] = oldRect[0];
                    rect[1] = oldRect[1];
                }
            }
        }

        this.surfaceRects = null;
    }

});
