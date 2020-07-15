/**
 * @class Ext.chart.series.sprite.StackedCartesian
 * @extends Ext.chart.series.sprite.Cartesian
 *
 * Stacked cartesian sprite.
 */
Ext.define('Ext.chart.series.sprite.StackedCartesian', {
    extend: 'Ext.chart.series.sprite.Cartesian',
    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @private
                 * @cfg {Number} [groupCount=1] The number of items (e.g. bars) in a group.
                 */
                groupCount: 'number',

                /**
                 * @private
                 * @cfg {Number} [groupOffset=0] The group index of the series sprite.
                 */
                groupOffset: 'number',

                /**
                 * @private
                 * @cfg {Object} [dataStartY=null] The starting point of the data
                 * used in the series.
                 */
                dataStartY: 'data'
            },
            defaults: {
                selectionTolerance: 20,
                groupCount: 1,
                groupOffset: 0,
                dataStartY: null
            },
            triggers: {
                dataStartY: 'dataY,bbox'
            }
        }
    }
});
