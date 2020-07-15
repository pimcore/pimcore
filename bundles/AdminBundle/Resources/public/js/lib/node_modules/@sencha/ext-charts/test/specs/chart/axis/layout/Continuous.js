topSuite("Ext.chart.axis.layout.Continuous", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe('snapEnds', function() {

        it("should use majorTickSteps value instead of segmenter.diff method to determine the number of steps", function() {
            var chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: 200,
                height: 200,
                store: {
                    autoDestroy: true,
                    fields: ['category', 'value'],
                    data: [
                        { category: 7, value: 0.2 },
                        { category: 6, value: 0.7 },
                        { category: 5, value: 1.2 },
                        { category: 4, value: 0.5 },
                        { category: 3, value: 0.1 },
                        { category: 2, value: 0.4 },
                        { category: 1, value: 0   }
                    ]
                },
                axes: [{
                    type: 'numeric',
                    position: 'left',
                    maximum: 1,
                    minimum: 0,
                    majorTickSteps: 10
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: [{
                    type: 'bar',
                    xField: 'category',
                    yField: 'value'
                }]
            });

            chart.performLayout();

            var numericAxis = chart.getAxis(0);

            var layoutContext = numericAxis.getSprites()[0].getLayoutContext();

            expect(layoutContext.majorTicks.steps).toEqual(10);

            chart.destroy();
        });
    });
});
