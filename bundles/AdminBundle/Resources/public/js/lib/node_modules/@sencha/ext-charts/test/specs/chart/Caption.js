topSuite('Ext.chart.Caption', ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    var side = 400;

    var titleText = 'Title';

    var subtitleText = 'Subtitle';

    var creditsText = 'Credits';

    var titleConfig = {
        text: titleText,
        padding: 5
    };

    var subtitleConfig = {
        text: subtitleText,
        align: 'center'
    };

    var creditsConfig = {
        text: creditsText,
        docked: 'bottom'
    };

    var commonChartConfig = {
        renderTo: Ext.getBody(),

        width: side,
        height: side,

        legend: {
            type: 'dom',
            docked: 'bottom'
        },

        store: {
            data: [
                { x: 'A', y1: 2, y2: 6 },
                { x: 'B', y1: 3, y2: 5 },
                { x: 'C', y1: 1, y2: 7 }
            ]
        }
    };

    var cartesianChartConfig = Ext.merge(Ext.clone(commonChartConfig), {
        axes: [
            {
                type: 'category',
                position: 'bottom'
            },
            {
                type: 'numeric',
                position: 'left'
            }
        ],

        series: [
            {
                type: 'bar',
                stacked: false,
                xField: 'x',
                yField: ['y1', 'y2']
            }
        ]
    });

    var polarChartConfig = Ext.merge(Ext.clone(commonChartConfig), {
        axes: [
            {
                type: 'category',
                position: 'angular'
            },
            {
                type: 'numeric',
                position: 'radial'
            }
        ],
        series: [
            {
                type: 'radar',
                angleField: 'x',
                radiusField: 'y2',
                style: {
                    fillOpacity: 0.6
                }
            },
            {
                type: 'radar',
                angleField: 'x',
                radiusField: 'y1',
                style: {
                    fillOpacity: 0.6
                }
            }
        ]
    });

    var defaultCaptions = {
        title: titleConfig,
        subtitle: subtitleConfig,
        credits: creditsConfig
    };

    describe('cartesian layout', function() {
        var chart;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("should have default captions properly positioned and chart surface properly sized", function() {
            var layoutDone;

            var config = Ext.merge(Ext.clone(cartesianChartConfig), {
                captions: Ext.clone(defaultCaptions),
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            runs(function() {
                chart = new Ext.chart.CartesianChart(config);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var captions = chart.getCaptions();

                var title = captions.title;

                var subtitle = captions.subtitle;

                var credits = captions.credits;

                var chartSurface = chart.getSurface('chart');

                var captionSurface = chart.getSurface(title.surfaceName);

                var chartSurfaceRect = chartSurface.getRect();

                var captionSurfaceRect = captionSurface.getRect();

                var titleBbox = title.getSprite().getBBox();

                var subtitleBbox = subtitle.getSprite().getBBox();

                var creditsBBox = credits.getSprite().getBBox();

                expect(titleBbox.y >= 0).toBe(true);
                expect(subtitleBbox.y >= (titleBbox.y + titleBbox.height)).toBe(true);

                expect(chartSurfaceRect[1] >= (subtitleBbox.y + subtitleBbox.height)).toBe(true);
                expect(chartSurfaceRect[3] <= (captionSurfaceRect[3] - (subtitleBbox.y + subtitleBbox.height) - credits.getRect()[3])).toBe(true);

                expect(creditsBBox.x + creditsBBox.height <= captionSurfaceRect[3]).toBe(true);
            });
        });

        it("should have weighted captions properly positioned and chart surface properly sized", function() {
            var layoutDone;

            var config = Ext.merge(Ext.clone(cartesianChartConfig), {
                captions: {
                    title: Ext.merge(Ext.clone(titleConfig), {
                        docked: 'top',
                        weight: 1
                    }),
                    subtitle: Ext.merge(Ext.clone(subtitleConfig), {
                        docked: 'top',
                        weight: 0
                    }),
                    credits: Ext.merge(Ext.clone(creditsConfig), {
                        docked: 'top',
                        weight: 2
                    })
                },
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            runs(function() {
                chart = new Ext.chart.CartesianChart(config);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var captions = chart.getCaptions();

                var title = captions.title;

                var subtitle = captions.subtitle;

                var credits = captions.credits;

                var chartSurface = chart.getSurface('chart');

                var captionSurface = chart.getSurface(title.surfaceName);

                var chartSurfaceRect = chartSurface.getRect();

                var captionSurfaceRect = captionSurface.getRect();

                var titleBbox = title.getSprite().getBBox();

                var subtitleBbox = subtitle.getSprite().getBBox();

                var creditsBBox = credits.getSprite().getBBox();

                expect(subtitleBbox.y >= 0).toBe(true);
                expect(titleBbox.y >= (subtitleBbox.y + subtitleBbox.height)).toBe(true);
                expect(creditsBBox.y >= (titleBbox.y + titleBbox.height)).toBe(true);

                expect(chartSurfaceRect[1] >= (creditsBBox.y + creditsBBox.height)).toBe(true);
                expect(chartSurfaceRect[3] <= (captionSurfaceRect[3] - (creditsBBox.y + creditsBBox.height))).toBe(true);
            });
        });

        it("should have style config values proxied to sprite attributes", function() {
            var layoutDone;

            var config = Ext.merge(Ext.clone(cartesianChartConfig), {
                captions: {
                    title: Ext.merge(Ext.clone(titleConfig), {
                        style: {
                            fontSize: 8,
                            fontWeight: 'lighter',
                            fontFamily: 'Verdana'
                        }
                    }),
                    subtitle: Ext.merge(Ext.clone(subtitleConfig), {
                        style: {
                            fontSize: 14,
                            fontWeight: 'bold'
                        }
                    }),
                    credits: Ext.merge(Ext.clone(creditsConfig), {
                        style: {
                            fontSize: 20,
                            fontWeight: '300'
                        }
                    })
                },
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            runs(function() {
                chart = new Ext.chart.CartesianChart(config);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var captions = chart.getCaptions();

                var title = captions.title;

                var subtitle = captions.subtitle;

                var credits = captions.credits;

                var titleSprite = title.getSprite();

                var subtitleSprite = subtitle.getSprite();

                var creditsSprite = credits.getSprite();

                expect(titleSprite.attr.text).toBe(titleText);
                expect(subtitleSprite.attr.text).toBe(subtitleText);
                expect(creditsSprite.attr.text).toBe(creditsText);

                expect(titleSprite.attr.fontSize).toBe('8px');
                expect(titleSprite.attr.fontWeight).toBe('lighter');
                expect(titleSprite.attr.fontFamily).toBe('Verdana');

                expect(subtitleSprite.attr.fontSize).toBe('14px');
                expect(subtitleSprite.attr.fontWeight).toBe('bold');

                expect(creditsSprite.attr.fontSize).toBe('20px');
                expect(creditsSprite.attr.fontWeight).toBe('300');
            });
        });

        it("should align properly", function() {
            var layoutDone;

            var config = Ext.merge(Ext.clone(cartesianChartConfig), {
                captions: {
                    title: Ext.merge(Ext.clone(titleConfig), {
                        align: 'center'
                    }),
                    subtitle: Ext.merge(Ext.clone(subtitleConfig), {
                        align: 'left'
                    }),
                    credits: Ext.merge(Ext.clone(creditsConfig), {
                        align: 'right'
                    })
                },
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            runs(function() {
                chart = new Ext.chart.CartesianChart(config);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var captions = chart.getCaptions();

                var title = captions.title;

                var subtitle = captions.subtitle;

                var credits = captions.credits;

                var seriesSurface = chart.getSurface('series');

                var seriesSurfaceRect = seriesSurface.getRect();

                var titleBbox = title.getSprite().getBBox();

                var subtitleBbox = subtitle.getSprite().getBBox();

                var creditsBBox = credits.getSprite().getBBox();

                var tolerance = 2; // in pixels

                expect(Ext.Number.isEqual(titleBbox.x, seriesSurfaceRect[0] + seriesSurfaceRect[2] / 2 - titleBbox.width / 2, tolerance)).toBe(true);
                expect(Ext.Number.isEqual(subtitleBbox.x, seriesSurfaceRect[0], tolerance)).toBe(true);
                expect(Ext.Number.isEqual(creditsBBox.x + creditsBBox.width, seriesSurfaceRect[0] + seriesSurfaceRect[2], tolerance)).toBe(true);
            });
        });

        it("should align to chart properly", function() {
            var layoutDone;

            var config = Ext.merge(Ext.clone(cartesianChartConfig), {
                captions: {
                    title: Ext.merge(Ext.clone(titleConfig), {
                        align: 'center',
                        alignTo: 'chart'
                    }),
                    subtitle: Ext.merge(Ext.clone(subtitleConfig), {
                        align: 'left',
                        alignTo: 'chart'
                    }),
                    credits: Ext.merge(Ext.clone(creditsConfig), {
                        align: 'right',
                        alignTo: 'chart'
                    })
                },
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            runs(function() {
                chart = new Ext.chart.CartesianChart(config);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var captions = chart.getCaptions();

                var title = captions.title;

                var subtitle = captions.subtitle;

                var credits = captions.credits;

                var seriesSurface = chart.getSurface('series');

                var chartSurface = chart.getSurface('chart');

                var chartSurfaceRect = chartSurface.getRect();

                var titleBbox = title.getSprite().getBBox();

                var subtitleBbox = subtitle.getSprite().getBBox();

                var creditsBBox = credits.getSprite().getBBox();

                var tolerance = 2; // in pixels

                expect(Ext.Number.isEqual(titleBbox.x, chartSurfaceRect[0] + chartSurfaceRect[2] / 2 - titleBbox.width / 2, tolerance)).toBe(true);
                expect(Ext.Number.isEqual(subtitleBbox.x, chartSurfaceRect[0], tolerance)).toBe(true);
                expect(Ext.Number.isEqual(creditsBBox.x + creditsBBox.width, chartSurfaceRect[0] + chartSurfaceRect[2], tolerance)).toBe(true);

            });
        });
    });
});
