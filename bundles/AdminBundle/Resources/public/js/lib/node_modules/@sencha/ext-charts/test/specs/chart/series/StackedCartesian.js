topSuite("Ext.chart.series.StackedCartesian",
    ['Ext.chart.*', 'Ext.data.ArrayStore', 'Ext.app.ViewController',
        'Ext.Container', 'Ext.layout.Fit'],
function() {

    describe('sprites', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should create the right number of sprites', function() {
            var layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',

                    renderTo: document.body,
                    width: 500,
                    height: 500,

                    animation: false,

                    legend: {
                        docked: 'right'
                    },

                    axes: [{
                        type: 'numeric',
                        position: 'left',
                        fields: 'data1',
                        grid: true,
                        minimum: 0
                    }, {
                        type: 'category',
                        position: 'bottom',
                        fields: 'month',
                        grid: true
                    }],
                    series: [],
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

                chart.setStore(new Ext.data.Store({
                    autoLoad: true,

                    fields: ['month', 'data1', 'data2', 'data3', 'data4', 'other'],

                    data: [{
                        month: "Jan",
                        data1: 20,
                        data2: 37,
                        data3: 35,
                        data4: 4,
                        other: 4
                    }],

                    listeners: {
                        load: function() {
                            var oldSeries = chart.getSeries();

                            if (oldSeries) {
                                chart.removeSeries(oldSeries);
                            }

                            chart.setSeries([{
                                type: 'bar',
                                title: ['IE', 'Firefox', 'Chrome', 'Safari'],
                                xField: 'month',
                                yField: ['data1', 'data2', 'data3', 'data4'],
                                renderer: function() {
                                    return {};
                                }
                            }]);
                        }
                    }
                }));
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var sprites = chart.getSeries()[0].getSprites();

                expect(sprites.length).toBe(4);
                expect(chart.getLegendStore().getCount()).toBe(4);
                expect(chart.getLegend().getSprites().length).toBe(4);
            });
        });
    });

    describe('highlight', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should propagate to "items" and "markers" templates of all MarkerHolder sprites', function() {
            var layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        fields: ['x', 'y1', 'y2', 'y3'],
                        data: [
                            {
                                x: 'one',
                                y1: 1,
                                y2: 2,
                                y3: 3
                            },
                            {
                                x: 'two',
                                y1: 2,
                                y2: 3,
                                y3: 4
                            }
                        ]
                    },
                    axes: [{
                        type: 'numeric',
                        position: 'left'
                    }, {
                        type: 'category',
                        position: 'bottom'
                    }],
                    series: [{
                        type: 'area',
                        xField: 'x',
                        yField: [ 'y1', 'y2', 'y3' ],
                        marker: {
                            opacity: 0,
                            scaling: 0.01
                        },
                        highlightCfg: {
                            opacity: 1,
                            scaling: 1.5
                        }
                    }, {
                        type: 'bar',
                        stacked: false,
                        xField: 'x',
                        yField: [ 'y1', 'y2', 'y3' ],
                        highlight: true
                    }],
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
                var seriesList = chart.getSeries(),
                    i, j, series, sprites, sprite, items, markers, style;

                for (j = 0; j < seriesList.length; j++) {
                    series = seriesList[j];
                    sprites = series.getSprites();

                    for (i = 0; i < sprites.length; i++) {
                        sprite = sprites[i];

                        if (sprite.isMarkerHolder) {
                            items = sprite.getMarker('items');

                            if (items) {
                                // Bar series will have the 'items' markers, but not 'markers' markers.
                                style = items.getTemplate().modifiers.highlight.getStyle();
                                // Default highlight style (when 'highlight: true' is used)
                                // for series is yellow fill ('#ffff00') and red stroke ('#ff0000').
                                expect(style.fillStyle).toBe('#ffff00');
                                expect(style.strokeStyle).toBe('#ff0000');
                            }

                            markers = sprite.getMarker('markers');

                            if (markers) {
                                // Area series will have the 'markers' markers, but not the 'items' markers.
                                style = markers.getTemplate().modifiers.highlight.getStyle();
                                // 'opacity' and 'scaling' are sprite attribute aliases which
                                // expand into 'globalAlpha' and 'scalingX/Y'.
                                expect(style.globalAlpha).toBe(1);
                                expect(style.scalingX).toBe(1.5);
                                expect(style.scalingY).toBe(1.5);
                            }
                        }
                    }
                }
            });
        });
    });
});
