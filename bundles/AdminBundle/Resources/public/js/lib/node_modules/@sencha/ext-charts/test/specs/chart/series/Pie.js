topSuite("Ext.chart.series.Pie", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe("betweenAngle", function() {
        it("should return false if the gap between start and end angles is zero", function() {
            var proto = Ext.chart.series.Pie.prototype,
                betweenAngle = proto.betweenAngle,
                context1 = {
                    // eslint-disable-next-line brace-style
                    getClockwise: function() { return true; },
                    rotationOffset: proto.rotationOffset
                },
                context2 = {
                    // eslint-disable-next-line brace-style
                    getClockwise: function() { return true; },
                    rotationOffset: context1.rotationOffset + 0.123
                };

            var result = betweenAngle.call(context1, -0.5, 0, 0);

            expect(result).toBe(false);

            result = betweenAngle.call(context1, -0.5, 1.1234567, 1.1234567);
            expect(result).toBe(false);

            result = betweenAngle.call(context2, -0.5, 0, 0);
            expect(result).toBe(false);

            result = betweenAngle.call(context2, -0.5, 1.1234567, 1.1234567);
            expect(result).toBe(false);
        });
    });

    describe('label', function() {
        (Ext.isSafari7 ? xit : it)('should not attempt to destroy labels marker in series with no label config', function() {
            var chart;

            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        fields: ['name', 'value'],
                        data: [{
                            name: 'A', value: 10
                        }, {
                            name: 'B', value: 70
                        }, {
                            name: 'C', value: 20
                        }, {
                            name: 'D', value: 20
                        }]
                    },
                    series: [{
                        type: 'pie',
                        angleField: 'value',
                        showInLegend: false,
                        style: {
                            stroke: '#ffffff',
                            'stroke-width': 2
                        },
                        highlight: true,
                        highlightCfg: {
                            margin: 2
                        },
                        donut: 45
                    }]
                });
            });

            waitsFor(function() {
                // wait till sprites have rendered
                return !chart.getSeries()[0].getSprites()[0].getDirty();
            });

            runs(function() {
                chart.getStore().loadData([{
                    name: 'E', value: 20
                }, {
                    name: 'F', value: 35
                }, {
                    name: 'G', value: 25
                }]);
            });

            waitsFor(function() {
                // wait till sprites have rendered
                return !chart.getSeries()[0].getSprites()[0].getDirty();
            });

            runs(function() {
                Ext.destroy(chart);
            });
        });
    });
});
