topSuite("Ext.chart.series.Gauge", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe('series renderer', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should be called with the right index', function() {
            var indexes = [],
                layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,
                    series: {
                        type: 'gauge',
                        donut: 30,
                        value: 60,
                        minimum: 100,
                        maximum: 800,
                        needle: true,
                        needleLength: 95,
                        needleWidth: 8,
                        totalAngle: Math.PI,
                        label: {
                            fontSize: 12,
                            fontWeight: 'bold'
                        },
                        colors: ['maroon', 'blue', 'lightgray', 'red'],
                        sectors: [{
                            end: 300,
                            label: 'Cold',
                            color: 'dodgerblue'
                        }, {
                            end: 600,
                            label: 'Temp.',
                            color: 'lightgray'
                        }, {
                            end: 800,
                            label: 'Hot',
                            color: 'tomato'
                        }],
                        renderer: function(sprite, config, rendererData, spriteIndex) {
                            indexes.push(spriteIndex);
                        }
                    },
                    listeners: {
                        layout: function() {
                            layoutDone = true;
                        }
                    }
                });
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                expect(indexes[0]).toEqual(1);
                expect(indexes[1]).toEqual(2);
                expect(indexes[2]).toEqual(3);
            });
        });
    });
});
