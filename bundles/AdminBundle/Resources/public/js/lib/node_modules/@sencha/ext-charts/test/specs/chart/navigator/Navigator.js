topSuite('Ext.chart.navigator.Navigator', [
    'Ext.chart.*',
    'Ext.app.ViewModel',
    'Ext.data.ArrayStore',
    'Ext.data.field.Field',
    'Ext.data.proxy.Memory'
], function() {
    function generateData() {
        var data = [],
            increment = Math.PI / 18,
            k = 10,
            a = 0,
            i, ln;

        for (i = 0, ln = 100; i < ln; i++) {
            data.push({
                x: a,
                sin: k * Math.sin(a),
                cos: k * Math.cos(a)
            });
            a += increment;
        }

        return data;
    }

    function getCfg() {
        return {
            renderTo: Ext.getBody(),
            xtype: 'chartnavigator',
            width: 400,
            height: 400,

            chart: {
                xtype: 'cartesian',

                interactions: {
                    type: 'panzoom',
                    zoomOnPanGesture: false,
                    axes: {
                        left: {
                            allowPan: false,
                            allowZoom: false
                        }
                    }
                },

                store: {
                    data: generateData()
                },

                axes: [
                    {
                        type: 'numeric',
                        position: 'left'
                    },
                    {
                        id: 'bottom',
                        type: 'category',
                        position: 'bottom'
                    }
                ],

                series: [
                    {
                        type: 'line',
                        xField: 'x',
                        yField: 'sin'
                    },
                    {
                        type: 'line',
                        xField: 'x',
                        yField: 'cos'
                    }
                ]
            }
        };
    }

    describe('layout', function() {
        var chartNavigator, layoutDone;

        afterEach(function() {
            chartNavigator = Ext.destroy(chartNavigator);
            layoutDone = false;
        });

        it('should span series', function() {
            runs(function() {
                chartNavigator = Ext.create(Ext.merge({
                    navigator: {
                        axis: 'bottom',
                        span: 'series',

                        listeners: {
                            layout: function() {
                                layoutDone = true;
                            }
                        }
                    }
                }, getCfg()));
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var chart = chartNavigator.getChart(),
                    navigator = chartNavigator.getNavigator();

                var chartRect = chart.getMainRect();

                var navigatorRect = navigator.getSurface('overlay').getRect();

                expect(chartRect[0]).toBe(navigatorRect[0]);
                expect(chartRect[2]).toBe(navigatorRect[2]);
            });
        });

        it('should span chart', function() {
            runs(function() {
                chartNavigator = Ext.create(Ext.merge({
                    navigator: {
                        axis: 'bottom',
                        span: 'chart',

                        listeners: {
                            layout: function() {
                                layoutDone = true;
                            }
                        }
                    }
                }, getCfg()));
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var navigator = chartNavigator.getNavigator();

                var navigatorRect = navigator.getSurface('overlay').getRect();

                expect(navigatorRect[0]).toBe(0);
                expect(navigatorRect[2]).toBe(navigator.el.getSize().width);

            });
        });
    });

    describe('view model', function() {
        var chartNavigator, layoutDone;

        afterEach(function() {
            chartNavigator = Ext.destroy(chartNavigator);
            layoutDone = false;
        });

        it('should span series', function() {
            var VM = new Ext.app.ViewModel({
                stores: {
                    dataStore: {
                        proxy: {
                            type: 'memory'
                        },
                        fields: ['x', 'y'],
                        data: [
                            {
                                x: 'Data 1',
                                y: 779
                            },
                            {
                                x: 'Data 2',
                                y: 67
                            },
                            {
                                x: 'Data 3',
                                y: 268
                            },
                            {
                                x: 'Data 4',
                                y: 16
                            },
                            {
                                x: 'Data 5',
                                y: 725
                            },
                            {
                                x: 'Data 6',
                                y: 886
                            }
                        ]
                    }
                }

            });

            runs(function() {
                chartNavigator = Ext.create({
                    xtype: 'chartnavigator',
                    renderTo: document.body,
                    width: 600,
                    height: 350,

                    viewModel: VM,

                    chart: {
                        xtype: 'cartesian',
                        bind: '{dataStore}',
                        axes: [
                            {
                                id: 'foo',
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
                                type: 'area',
                                xField: 'x',
                                yField: 'y'
                            }
                        ]
                    },

                    navigator: {
                        axis: 'foo',

                        listeners: {
                            layout: function() {
                                layoutDone = true;
                            }
                        }
                    }
                });
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var navigator = chartNavigator.getNavigator(),
                    seriesSprite = navigator.getSeries()[0].getSprites()[0];

                expect(seriesSprite.attr.dataY.length).toBe(6);
                expect(seriesSprite.attr.dataY[2]).toBe(268);
            });
        });
    });

});
