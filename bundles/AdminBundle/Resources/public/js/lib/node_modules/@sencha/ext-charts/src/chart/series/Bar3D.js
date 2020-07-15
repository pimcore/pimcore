/**
 * @class Ext.chart.series.Bar3D
 * @extends Ext.chart.series.Bar
 *
 * Creates a 3D Bar or 3D Column Chart (depending on the value of the
 * {@link Ext.chart.CartesianChart#flipXY flipXY} config).
 *
 * Note: 'bar3d' series is meant to be used with the
 * {@link Ext.chart.axis.Category 'category3d'} axis as its x-axis.
 *
 *     @example
 *     Ext.create({
 *        xtype: 'cartesian', 
 *        renderTo: Ext.getBody(),
 *        width: 600,
 *        height: 400,
 *        innerPadding: '0 10 0 10',
 *        store: {
 *            fields: ['name', 'apples', 'oranges'],
 *            data: [{
 *                name: 'Eric',
 *                apples: 10,
 *                oranges: 3
 *            }, {
 *                name: 'Mary',
 *                apples: 7,
 *                oranges: 2
 *            }, {
 *                name: 'John',
 *                apples: 5,
 *                oranges: 2
 *            }, {
 *                name: 'Bob',
 *                apples: 2,
 *                oranges: 3
 *            }, {
 *                name: 'Joe',
 *                apples: 19,
 *                oranges: 1
 *            }, {
 *                name: 'Macy',
 *                apples: 13,
 *                oranges: 4
 *            }]
 *        },
 *        axes: [{
 *            type: 'numeric3d',
 *            position: 'left',
 *            fields: ['apples', 'oranges'],
 *            title: {
 *                text: 'Inventory',
 *                fontSize: 15
 *            },
 *            grid: {
 *                odd: {
 *                    fillStyle: 'rgba(255, 255, 255, 0.06)'
 *                },
 *                even: {
 *                    fillStyle: 'rgba(0, 0, 0, 0.03)'
 *                }
 *            }
 *        }, {
 *            type: 'category3d',
 *            position: 'bottom',
 *            title: {
 *                text: 'People',
 *                fontSize: 15
 *            },
 *            fields: 'name'
 *        }],
 *        series: {
 *            type: 'bar3d',
 *            xField: 'name',
 *            yField: ['apples', 'oranges']
 *        }
 *     });
 */
Ext.define('Ext.chart.series.Bar3D', {
    extend: 'Ext.chart.series.Bar',

    requires: [
        'Ext.chart.series.sprite.Bar3D',
        'Ext.chart.sprite.Bar3D'
    ],

    alias: 'series.bar3d',
    type: 'bar3d',
    seriesType: 'bar3dSeries',
    is3D: true,

    config: {
        itemInstancing: {
            type: 'bar3d',
            animation: {
                customDurations: {
                    x: 0,
                    y: 0,
                    width: 0,
                    height: 0,
                    depth: 0
                }
            }
        },
        highlightCfg: {
            opacity: 0.8
        }
    },

    /**
     * For 3D series, it's quite the opposite. It would be extremely odd,
     * if top segments were rendered as if they were under the bottom ones.
     */
    reversedSpriteZOrder: false,

    updateXAxis: function(xAxis, oldXAxis) {
        //<debug>
        if (xAxis.type !== 'category3d') {
            Ext.raise("'bar3d' series should be used with a 'category3d' axis." +
                " Please refer to the 'bar3d' series docs.");
        }

        //</debug>
        this.callParent([xAxis, oldXAxis]);
    },

    getDepth: function() {
        var sprite = this.getSprites()[0];

        return sprite ? (sprite.depth || 0) : 0;
    }

});
