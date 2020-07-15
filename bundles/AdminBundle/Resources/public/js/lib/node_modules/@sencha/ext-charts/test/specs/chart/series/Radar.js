topSuite("Ext.chart.series.Radar", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {

    describe("center", function() {

        var chart, layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
            layoutDone = false;
        });

        it("should be set to a proper value when new series is added", function() {
            runs(function() {
                chart = new Ext.chart.PolarChart({
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,
                    innerPadding: 0,
                    insetPadding: 0,
                    store: {
                        data: [
                            {
                                cat: 'A',
                                priority: 12,
                                other: 8,
                                foo: 6
                            },
                            {
                                cat: 'B',
                                priority: 15,
                                other: 10,
                                foo: 17
                            },
                            {
                                cat: 'C',
                                priority: 10,
                                other: 20,
                                foo: 14
                            }
                        ]
                    },
                    axes: [
                        {
                            type: 'numeric',
                            position: 'radial',
                            grid: true
                        },
                        {
                            type: 'category',
                            position: 'angular',
                            grid: true
                        }
                    ],
                    series: [
                        {
                            type: 'radar',
                            radiusField: 'priority',
                            angleField: 'cat',
                            style: {
                                fillOpacity: 0.5
                            }
                        },
                        {
                            type: 'radar',
                            radiusField: 'other',
                            angleField: 'cat',
                            style: {
                                fillOpacity: 0.5
                            }
                        }
                    ],
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

                chart.addSeries({
                    type: 'radar',
                    angleField: 'cat',
                    radiusField: 'foo',
                    style: {
                        fillOpacity: 0.5
                    }
                });
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var seriesList = chart.getSeries(),
                    ln = seriesList.length,
                    expectation = 200,
                    i = 0,
                    series, sprite, center;

                for (; i < ln; i++) {
                    series = seriesList[i];
                    center = series.getCenter();
                    expect(center[0]).toBe(expectation);
                    expect(center[1]).toBe(expectation);
                    sprite = series.getSprites()[0];
                    expect(sprite.attr.translationX).toBe(expectation);
                    expect(sprite.attr.translationY).toBe(expectation);
                }
            });

        });
    });
});
