topSuite("Ext.chart.axis.Time", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    beforeEach(function() {
        // Silence Sencha download server warnings
        spyOn(Ext.log, 'warn');
    });

    describe('renderer', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should work with custom renderers even when "dateFormat" is set', function() {
            var axisRendererCallCount = 0,
                lastAxisRendererResult,
                axes, timeAxis, layoutEndSpy;

            chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                insetPadding: 60,
                store: new Ext.data.Store({
                    data: [
                        { value: 1, time: new Date('Jun 01 2015 12:00') },
                        { value: 3, time: new Date('Jun 01 2015 13:00') },
                        { value: 2, time: new Date('Jun 01 2015 14:00') }
                    ]
                }),
                series: {
                    type: 'line',
                    xField: 'time',
                    yField: 'value'
                },
                axes: [
                    {
                        type: 'numeric',
                        position: 'left'
                    },
                    {
                        type: 'time',
                        position: 'bottom',
                        dateFormat: 'F j g:i A',
                        renderer: function() {
                            axisRendererCallCount++;

                            return lastAxisRendererResult = 'hello';
                        }
                    }
                ]
            });

            layoutEndSpy = spyOn(chart, 'onLayoutEnd').andCallThrough();

            waitsForSpy(layoutEndSpy, "chart layout to finish");

            runs(function() {
                expect(axisRendererCallCount).toBeGreaterThan(3);

                axes = chart.getAxes();
                timeAxis = axes[1];
                axisRendererCallCount = 0;
                timeAxis.getSegmenter().setStep({
                    unit: Ext.Date.HOUR,
                    step: 1
                });
                layoutEndSpy.reset();
                chart.performLayout();
            });

            waitsForSpy(layoutEndSpy, "chart layout to finish");

            runs(function() {
                expect(axisRendererCallCount).toBe(3);
                expect(lastAxisRendererResult).toBe('hello');

                axisRendererCallCount = 0;
                lastAxisRendererResult = undefined;
                timeAxis.setRenderer(function() {
                    axisRendererCallCount++;

                    return lastAxisRendererResult = 'hi';
                });
                // New custom renderer should trigger axis and chart layouts.
                layoutEndSpy.reset();
            });

            waitsForSpy(layoutEndSpy, "chart layout to finish");

            runs(function() {
                expect(axisRendererCallCount).toBe(3);
                expect(lastAxisRendererResult).toBe('hi');

                timeAxis.setRenderer(null);

                // No user renderer, but dateFormat is set, should create a default renderer
                // based on dateFormat.
                var defaultRenderer = timeAxis.getRenderer();

                expect(defaultRenderer.isDefault).toBe(true);
            });
        });
    });

});
