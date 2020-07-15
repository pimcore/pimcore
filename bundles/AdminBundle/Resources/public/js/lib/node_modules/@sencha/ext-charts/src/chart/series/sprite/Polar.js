/**
 * Polar sprite.
 */
Ext.define('Ext.chart.series.sprite.Polar', {
    extend: 'Ext.chart.series.sprite.Series',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [centerX=0] The central point of the series on the x-axis.
                 */
                centerX: 'number',

                /**
                 * @cfg {Number} [centerY=0] The central point of the series on the y-axis.
                 */
                centerY: 'number',

                /**
                 * @cfg {Number} [startAngle=0] The starting angle of the polar series.
                 */
                startAngle: 'number',

                /**
                 * @cfg {Number} [endAngle=Math.PI] The ending angle of the polar series.
                 */
                endAngle: 'number',

                /**
                 * @cfg {Number} [startRho=0] The starting radius of the polar series.
                 */
                startRho: 'number',

                /**
                 * @cfg {Number} [endRho=150] The ending radius of the polar series.
                 */
                endRho: 'number',

                /**
                 * @cfg {Number} [baseRotation=0] The starting rotation of the polar series.
                 */
                baseRotation: 'number'
            },
            defaults: {
                centerX: 0,
                centerY: 0,
                startAngle: 0,
                endAngle: Math.PI,
                startRho: 0,
                endRho: 150,
                baseRotation: 0
            },
            triggers: {
                centerX: 'bbox',
                centerY: 'bbox',
                startAngle: 'bbox',
                endAngle: 'bbox',
                startRho: 'bbox',
                endRho: 'bbox',
                baseRotation: 'bbox'
            }
        }
    },

    updatePlainBBox: function(plain) {
        var attr = this.attr;

        plain.x = attr.centerX - attr.endRho;
        plain.y = attr.centerY + attr.endRho;
        plain.width = attr.endRho * 2;
        plain.height = attr.endRho * 2;
    }
});
