/**
 * Base class for all series sprites.
 * Defines attributes common to all series sprites, like data in x/y directions and its
 * min/max values, and configs, like the {@link Ext.chart.series.Series} instance that manages
 * the sprite.
 *
 */
Ext.define('Ext.chart.series.sprite.Series', {
    extend: 'Ext.draw.sprite.Sprite',
    mixins: {
        markerHolder: 'Ext.chart.MarkerHolder'
    },

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [dataMinX=0] Data minimum on the x-axis.
                 */
                dataMinX: 'number',

                /**
                 * @cfg {Number} [dataMaxX=1] Data maximum on the x-axis.
                 */
                dataMaxX: 'number',

                /**
                 * @cfg {Number} [dataMinY=0] Data minimum on the y-axis.
                 */
                dataMinY: 'number',

                /**
                 * @cfg {Number} [dataMaxY=1] Data maximum on the y-axis.
                 */
                dataMaxY: 'number',

                /**
                 * @cfg {Array} [rangeX=null] Data range derived from all the series bound
                 * to the x-axis.
                 */
                rangeX: 'data',
                /**
                 * @cfg {Array} [rangeY=null] Data range derived from all the series bound
                 * to the y-axis.
                 */
                rangeY: 'data',

                /**
                 * @cfg {Object} [dataX=null] Data items on the x-axis.
                 */
                dataX: 'data',

                /**
                 * @cfg {Object} [dataY=null] Data items on the y-axis.
                 */
                dataY: 'data',

                /**
                 * @cfg {Object} [labels=null] Labels used in the series.
                 */
                labels: 'default',

                /**
                 * @cfg {Number} [labelOverflowPadding=10] Padding around labels to determine
                 * overlap.
                 */
                labelOverflowPadding: 'number'
            },

            defaults: {
                dataMinX: 0,
                dataMaxX: 1,
                dataMinY: 0,
                dataMaxY: 1,
                rangeX: null,
                rangeY: null,
                dataX: null,
                dataY: null,
                labels: null,
                labelOverflowPadding: 10
            },

            triggers: {
                dataX: 'bbox',
                dataY: 'bbox',
                dataMinX: 'bbox',
                dataMaxX: 'bbox',
                dataMinY: 'bbox',
                dataMaxY: 'bbox'
            }
        }
    },

    config: {
        /**
         * @private
         * @cfg {Object} store The store that is passed to the renderer.
         */
        store: null,

        series: null,

        /**
         * @cfg {String} field The store field used by the series.
         */
        field: null
    }
});
