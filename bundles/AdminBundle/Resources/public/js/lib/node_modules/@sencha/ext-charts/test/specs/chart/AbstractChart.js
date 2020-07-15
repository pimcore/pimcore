/* global topSuite */
topSuite("Ext.chart.AbstractChart", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    var chart, store,
        Model = Ext.define(null, {
            extend: 'Ext.data.Model',
            fields: ['label', 'value']
        });

    function makeStore(rows) {
        var data = [],
            i;

        for (i = 1; i <= rows; ++i) {
            data.push({
                label: 'Item' + i,
                value: i
            });
        }

        store = new Ext.data.Store({
            model: Model,
            data: data
        });
    }

    beforeEach(function() {
        // Tons of warnings regarding Sencha download server in the console
        spyOn(Ext.log, 'warn');
    });

    afterEach(function() {
        store = chart = Ext.destroy(chart, store);
        // cleanup gradients
        Ext.draw.gradient.GradientDefinition.gradients = {};
    });

    it('is defined', function() {
        expect(Ext.chart.AbstractChart).toBeDefined();
    });

    describe("stores", function() {
        function makeChart(storeOnSeries, chartCfg, seriesCfg) {
            var cfg = Ext.apply({
                xtype: 'cartesian',
                axes: [{
                    type: 'numeric',
                    position: 'left'
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                animation: false,
                series: Ext.apply({
                    type: 'bar',
                    xField: 'label',
                    yField: 'value'
                }, seriesCfg)
            }, chartCfg);

            if (storeOnSeries) {
                if (!cfg.series.store) {
                    cfg.series.store = makeStore(3);
                }
            }
            else {
                if (!cfg.store) {
                    cfg.store = makeStore(3);
                }
            }

            chart = new Ext.chart.CartesianChart(cfg);
        }

        function extractHasListeners(o) {
            var ret = {},
                key;

            for (key in o) {
                ret[key] = o[key];
            }

            delete ret._decr_;
            delete ret._incr_;

            return ret;
        }

        describe("store on the chart", function() {
            function makeStoreChart(chartCfg, seriesCfg) {
                makeChart(false, chartCfg, seriesCfg);
            }

            describe("configuration", function() {
                it("should accept a store id", function() {
                    store = new Ext.data.Store({
                        model: Model,
                        storeId: 'foo'
                    });
                    makeStoreChart({
                        store: 'foo'
                    });
                    expect(chart.getStore()).toBe(store);
                });

                it("should accept a store config", function() {
                    makeStoreChart({
                        store: {
                            model: Model,
                            data: [{}]
                        }
                    });
                    expect(chart.getStore().getCount()).toBe(1);
                    expect(chart.getStore().getModel()).toBe(Model);
                });

                it("should accept a store instance", function() {
                    makeStore(10);
                    makeStoreChart({
                        store: store
                    });
                    expect(chart.getStore()).toBe(store);
                });
            });

            describe("destruction", function() {
                it("should remove all listeners", function() {
                    makeStore(3);

                    var listeners = extractHasListeners(store.hasListeners);

                    makeStoreChart({
                        store: store
                    });
                    chart.destroy();
                    expect(extractHasListeners(store.hasListeners)).toEqual(listeners);
                });

                it("should not destroy the store by default", function() {
                    makeStore(3);
                    makeStoreChart({
                        store: store
                    });
                    chart.destroy();
                    expect(store.destroyed).toBe(false);
                });

                it("should destroy the store when the store has autoDestroy: true", function() {
                    makeStore(3);
                    store.setAutoDestroy(true);
                    makeStoreChart({
                        store: store
                    });
                    chart.destroy();
                    expect(store.destroyed).toBe(true);
                });
            });

            describe("change", function() {
                it("should fire 'storechange' event", function() {
                    var isFired = false,
                        store1 = new Ext.data.Store({
                            model: Model
                        }),
                        store2 = new Ext.data.Store({
                            model: Model
                        }),
                        param1, param2, param3;

                    makeStoreChart({
                        store: store1
                    });

                    chart.on('storechange', function(chart, newStore, oldStore) {
                        isFired = true;
                        param1 = chart;
                        param2 = newStore;
                        param3 = oldStore;
                    });

                    chart.setStore(store2);

                    expect(isFired).toEqual(true);
                    expect(param1).toEqual(chart);
                    expect(param2).toEqual(store2);
                    expect(param3).toEqual(store1);
                });
            });
        });

        describe("store on the series", function() {
            function makeSeriesChart(chartCfg, seriesCfg) {
                makeChart(true, chartCfg, seriesCfg);
            }

            describe("configuration", function() {
                it("should accept a store id", function() {
                    store = new Ext.data.Store({
                        model: Model,
                        storeId: 'foo'
                    });
                    makeSeriesChart(null, {
                        store: 'foo'
                    });
                    expect(chart.getStore().isEmptyStore).toBe(true);
                    expect(chart.getSeries()[0].getStore()).toBe(store);
                });

                it("should accept a store config", function() {
                    makeSeriesChart(null, {
                        store: {
                            model: Model,
                            data: [{}]
                        }
                    });
                    expect(chart.getStore().isEmptyStore).toBe(true);
                    expect(chart.getSeries()[0].getStore().getCount()).toBe(1);
                    expect(chart.getSeries()[0].getStore().getModel()).toBe(Model);
                });

                it("should accept a store instance", function() {
                    makeStore(10);
                    makeSeriesChart(null, {
                        store: store
                    });
                    expect(chart.getStore().isEmptyStore).toBe(true);
                    expect(chart.getSeries()[0].getStore()).toBe(store);
                });
            });

            describe("destruction", function() {
                it("should remove all listeners", function() {
                    makeStore(3);

                    var listeners = extractHasListeners(store.hasListeners);

                    makeSeriesChart(null, {
                        store: store
                    });
                    chart.destroy();
                    expect(extractHasListeners(store.hasListeners)).toEqual(listeners);
                });

                it("should not destroy the store by default", function() {
                    makeStore(3);
                    makeSeriesChart(null, {
                        store: store
                    });
                    chart.destroy();
                    expect(store.destroyed).toBe(false);
                });

                it("should destroy the store when the store has autoDestroy: true", function() {
                    makeStore(3);
                    store.setAutoDestroy(true);
                    makeSeriesChart(null, {
                        store: store
                    });
                    chart.destroy();
                    expect(store.destroyed).toBe(true);
                });

                it("should not destroy the store when destroying the series by default", function() {
                    makeStore(3);
                    makeSeriesChart(null, {
                        store: store
                    });
                    chart.setSeries([{
                        type: 'bar',
                        xField: 'label',
                        yField: 'value'
                    }]);
                    expect(store.destroyed).toBe(false);
                });

                it("should destroy the store when destroying the series when the store has autoDestroy: true", function() {
                    makeStore(3);
                    store.setAutoDestroy(true);
                    makeSeriesChart(null, {
                        store: store
                    });
                    chart.setSeries([{
                        type: 'bar',
                        xField: 'label',
                        yField: 'value'
                    }]);
                    expect(store.destroyed).toBe(true);
                });
            });

            describe("change", function() {
                it("should fire 'storechange' event", function() {
                    var isFired = false,
                        store1 = new Ext.data.Store({
                            model: Model
                        }),
                        store2 = new Ext.data.Store({
                            model: Model
                        }),
                        series, param1, param2, param3;

                    makeSeriesChart(null, {
                        store: store1
                    });

                    series = chart.getSeries()[0];

                    series.on('storechange', function(series, newStore, oldStore) {
                        isFired = true;
                        param1 = series;
                        param2 = newStore;
                        param3 = oldStore;
                    });

                    series.setStore(store2);

                    expect(isFired).toEqual(true);
                    expect(param1).toEqual(series);
                    expect(param2).toEqual(store2);
                    expect(param3).toEqual(store1);
                });
            });

        });
    });

    describe('adding and removing series', function() {

        var layoutDone;

        beforeEach(function() {
            store = new Ext.data.Store({
                fields: ['x', 'y', 'z'],
                data: [
                    { x: 0, y: 0, z: 0 },
                    { x: 1, y: 1, z: 1 }
                ]
            });
            chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                store: store,
                axes: [{
                    position: 'left',
                    type: 'numeric'
                }, {
                    position: 'bottom',
                    type: 'numeric'
                }],
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });
        });

        afterEach(function() {
            layoutDone = false;
        });

        it('should start with no series', function() {
            expect(chart.getSeries().length).toBe(0);
        });

        it('should add and remove series using setSeries', function() {
            var series;

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                chart.setSeries([{
                    type: 'line',
                    xField: 'x',
                    yField: 'y',
                    id: 'xySeries'
                }]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();

                expect(series.length).toBe(1);
                expect(series[0].getId()).toBe('xySeries');

                chart.setSeries([{
                    type: 'line',
                    xField: 'x',
                    yField: 'z',
                    id: 'xzSeries'
                }]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();

                expect(series.length).toBe(1);
                expect(series[0].getId()).toBe('xzSeries');
            });
        });

        it('should add series using addSeries', function() {
            var series;

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                chart.addSeries([{
                    type: 'line',
                    xField: 'x',
                    yField: 'y',
                    id: 'xySeries'
                }]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();

                expect(series.length).toBe(1);
                expect(series[0].getId()).toBe('xySeries');

                chart.addSeries({
                    type: 'line',
                    xField: 'x',
                    yField: 'z',
                    id: 'xzSeries'
                });
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();

                expect(series.length).toBe(2);
                expect(series[0].getId()).toBe('xySeries');
                expect(series[1].getId()).toBe('xzSeries');
            });
        });

        it('should remove series using removeSeries', function() {
            var series;

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                chart.addSeries([{
                    type: 'line',
                    xField: 'x',
                    yField: 'y',
                    id: 'xySeries'
                }, {
                    type: 'line',
                    xField: 'x',
                    yField: 'z',
                    id: 'xzSeries'
                }]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();

                expect(series.length).toBe(2);
                expect(series[0].getId()).toBe('xySeries');
                expect(series[1].getId()).toBe('xzSeries');

                // Remove Series id "xySeries", should leave only "xzSeries"
                chart.removeSeries('xySeries');
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                series = chart.getSeries();
                expect(series.length).toBe(1);
                expect(series[0].getId()).toBe('xzSeries');

                // Remove a Series by specifying the instance should leav no Series
                chart.removeSeries(series[0]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                expect(chart.getSeries().length).toBe(0);
            });
        });
    });

    describe('getInteraction', function() {
        it("should return a correct interaction based on its type", function() {
            makeStore(3);
            chart = new Ext.chart.CartesianChart({
                store: store,
                interactions: [
                    {
                        type: 'itemhighlight'
                    },
                    {
                        type: 'itemedit'
                    },
                    {
                        type: 'crosszoom'
                    }
                ],
                axes: [{
                    type: 'numeric',
                    position: 'left'
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: {
                    type: 'bar',
                    xField: 'label',
                    yField: 'value'
                }
            });

            var itemhighlight = chart.getInteraction('itemhighlight'),
                crosszoom = chart.getInteraction('crosszoom'),
                itemedit = chart.getInteraction('itemedit');

            expect(itemhighlight.isItemHighlight).toBe(true);
            expect(crosszoom.isCrossZoom).toBe(true);
            expect(itemedit.isItemEdit).toBe(true);
        });
    });

    describe('processData', function() {
        it('should refresh legend store', function() {
            var layoutEnd, processDataSpy;

            runs(function() {
                chart = new Ext.chart.PolarChart({
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    legend: {
                        docked: 'right'
                    },
                    store: {
                        data: [{
                            "name": "A",
                            "data1": 1
                        }, {
                            "name": "B",
                            "data1": 2
                        }]
                    },
                    series: {
                        type: 'pie3d',
                        angleField: 'data1',
                        label: {
                            field: 'name'
                        }
                    },
                    listeners: {
                        layout: function() {
                            layoutEnd = true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                layoutEnd = false;

                expect(chart.getLegend().getStore().getAt(0).get('name')).toBe('A');
                processDataSpy = spyOn(chart, 'processData').andCallThrough();
                chart.getStore().loadData([{
                    name: 'X',
                    data1: 24
                }, {
                    name: 'Y',
                    data1: 25
                }]);
                expect(processDataSpy).toHaveBeenCalled();
                expect(chart.getLegend().getStore().getAt(0).get('name')).toBe('X');
            });
        });
    });

    describe("update gradients", function() {
        beforeEach(function() {
            makeStore(3);
            chart = new Ext.chart.CartesianChart({
                store: store,
                axes: [{
                    type: 'numeric',
                    position: 'left'
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: {
                    type: 'bar',
                    xField: 'label',
                    yField: 'value',
                    style: {
                        fillStyle: 'url(#foo)'
                    }
                },
                gradients: [{
                    id: 'foo',
                    type: 'linear',
                    degrees: 270,
                    stops: [{
                        offset: 0,
                        color: '#78C5D6'
                    }, {
                        offset: 0.56,
                        color: '#F5D63D'
                    }, {
                        offset: 1,
                        color: '#BF62A6'
                    }]
                }]
            });
        });

        it("should create sprites with the correct gradient applied", function() {
            var seriesSprite = chart.getSeries()[0].sprites[0],
                fillStyle = seriesSprite.attr.fillStyle;

            expect(typeof fillStyle).toBe('object');
            expect(fillStyle.isGradient).toBe(true);
        });

        it("should update sprites when gradient has been updated", function() {
            var seriesSprite = chart.getSeries()[0].sprites[0],
                fillStyle = seriesSprite.attr.fillStyle,
                newGradient = [{
                    id: 'foo',
                    type: 'linear',
                    degrees: 270,
                    stops: [{
                        offset: 0,
                        color: '#000000'
                    }, {
                        offset: 0.56,
                        color: '#F5D63D'
                    }, {
                        offset: 1,
                        color: '#000000'
                    }]
                }];

            // first, make sure correct color is applied on initial construction
            expect(fillStyle.getStops()[0].color).toBe('#78c5d6');
            // now let's update the draw container with a slightly different gradient with the same id
            chart.setGradients(newGradient);
            // theme should get updated; check sprite again
            seriesSprite = chart.getSeries()[0].sprites[0];
            fillStyle = seriesSprite.attr.fillStyle;
            // should have different colors for updated gradient
            expect(fillStyle.getStops()[0].color).toBe('#000000');
        });
    });

    describe('legend render checking if legend is getting rendered along with Chart', function() {

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("legend should be rendered", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,
                    innerPadding: 10,
                    store: {
                        fields: ['name', 'data1'],
                        data: [[{
                            age: '0',
                            value: 400
                        }, {
                            age: '2',
                            value: 150
                        }, {
                            age: '4',
                            value: 120
                        }, {
                            age: '6',
                            value: 100
                        }, {
                            age: '8',
                            value: 1500
                        }]]
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
                    }
                });
            });

            runs(function() {
                var legend = chart.getLegend();

                expect(legend.rendered).toBe(true);
            });
        });
    });
});
