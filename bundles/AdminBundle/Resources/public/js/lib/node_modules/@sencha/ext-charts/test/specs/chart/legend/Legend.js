topSuite("Ext.chart.legend.Legend", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe('resize', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should schedule chart layout when the legend size changes', function() {
            var layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    innerPadding: 10,
                    // Create a polar chart with no data.
                    store: {
                        fields: ['name', 'data1'],
                        data: []
                    },
                    legend: {
                        type: 'dom',
                        docked: 'right'
                    },
                    series: {
                        type: 'pie',
                        highlight: true,
                        angleField: 'data1',
                        donut: 30
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
                layoutDone = false;

                // Add data to the chart.
                // That should populate the legend, which will change its size,
                // which should trigger another chart layout.
                chart.getStore().add({
                    name: 'metric one',
                    data1: 14
                }, {
                    name: 'metric two',
                    data1: 16
                }, {
                    name: 'metric three',
                    data1: 14
                }, {
                    name: 'metric four',
                    data1: 6
                }, {
                    name: 'metric five',
                    data1: 36
                });
                //
            });

            waitsFor(function() {
                // If the layout is not done, we will wait forever
                // or until the test times out.
                return layoutDone;
            });

        });
    });
});
