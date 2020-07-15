topSuite("Ext.chart.series.Pie.classic",
    [false, 'Ext.chart.*', 'Ext.data.ArrayStore'],
function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe('label.display', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should hide the labels if set to `none`', function() {
            var layoutDone;

            runs(function() {
                chart = new Ext.chart.PolarChart({
                    renderTo: document.body,
                    animation: false,
                    interactions: 'rotate',
                    height: 400,
                    width: 400,
                    innerPadding: 20,
                    series: {
                        type: 'pie',
                        angleField: 'data1',
                        label: {
                            field: 'name',
                            display: 'none'
                        }
                    },
                    store: {
                        fields: ['name', 'data1'],
                        data: [{
                            name: 'metric one',
                            data1: 200
                        }, {
                            name: 'metric two',
                            data1: 100
                        }]
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
                var series = chart.getSeries()[0];

                var labels = series.getSprites()[0].getMarker('labels');

                expect(labels.instances[0].hidden).toBe(false);
                expect(labels.instances[1].hidden).toBe(false);
                expect(labels.attr.hidden).toBe(true);

                series.setLabel({
                    display: 'inside'
                });

                expect(labels.instances[0].display).toBe('inside');
                expect(labels.instances[1].display).toBe('inside');
                expect(labels.instances[0].hidden).toBe(false);
                expect(labels.instances[1].hidden).toBe(false);
                expect(labels.attr.hidden).toBe(false);
            });
        });
    });
});
