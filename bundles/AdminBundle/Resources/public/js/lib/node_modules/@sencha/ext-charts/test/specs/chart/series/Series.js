topSuite("Ext.chart.series.Series",
    ['Ext.chart.*', 'Ext.data.ArrayStore', 'Ext.app.ViewController',
     'Ext.Container', 'Ext.layout.Fit'],
function() {
    var proto = Ext.chart.series.Series.prototype,
        synchronousLoad = true,
        proxyStoreLoad = Ext.data.ProxyStore.prototype.load,
        loadStore;

    beforeEach(function() {
        // Override so that we can control asynchronous loading
        loadStore = Ext.data.ProxyStore.prototype.load = function() {
            proxyStoreLoad.apply(this, arguments);

            if (synchronousLoad) {
                this.flushLoad.apply(this, arguments);
            }

            return this;
        };

        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    afterEach(function() {
        // Undo the overrides.
        Ext.data.ProxyStore.prototype.load = proxyStoreLoad;
    });

    describe('marker', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should toggle its visibility when the "showMarkers" config changes', function() {
            var layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: [
                            { x: 0, y: 1 },
                            { x: 1, y: 2 },
                            { x: 2, y: 1 }
                        ]
                    },
                    axes: [
                        {
                            type: 'numeric',
                            position: 'left'
                        },
                        {
                            type: 'numeric',
                            position: 'bottom'
                        }
                    ],
                    series: [{
                        type: 'line',
                        xField: 'x',
                        yField: 'y',
                        marker: true
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
                var series = chart.getSeries()[0],
                    seriesSprite = series.getSprites()[0];

                expect(seriesSprite.getMarker('markers')).toBeTruthy();
                series.setMarker(null);
                chart.redraw();
                expect(seriesSprite.getMarker('markers')).toBeFalsy();
                series.setMarker(true);
                chart.redraw();
                expect(seriesSprite.getMarker('markers')).toBeTruthy();

                var template = seriesSprite.getMarker('markers').getTemplate();

                // Expect the added marker to be themed.
                expect(template.attr.fillStyle).toBe('#94ae0a');
                expect(template.attr.strokeStyle).toBe('#566606');

                expect(template.modifiers.highlight).toBeTruthy();

                series.setShowMarkers(false);
                expect(template.attr.hidden).toBe(true);
                series.setShowMarkers(true);
                expect(template.attr.hidden).toBe(false);
            });
        });
    });

    describe('label', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should allow for dynamic updates of the "field" config', function() {
            var layoutDone;

            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    theme: 'green',
                    store: {
                        fields: ['name', 'data1'],
                        data: [{
                            name: 'metric one',
                            name2: 'metric 1',
                            data1: 14
                        }, {
                            name: 'metric two',
                            name2: 'metric 2',
                            data1: 16
                        }]
                    },
                    series: {
                        id: 'mySeries',
                        type: 'pie',
                        highlight: true,
                        angleField: 'data1',
                        label: {
                            field: 'name',
                            display: 'rotate'
                        },
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
                var series = chart.get('mySeries'),
                    label = series.getLabel();

                expect(label.get(0).text).toBe('metric one');
                expect(label.get(1).text).toBe('metric two');

                series.setLabel({
                    field: 'name2'
                });

                expect(label.get(0).text).toBe('metric 1');
                expect(label.get(1).text).toBe('metric 2');
            });
        });
    });

    describe('resolveListenerScope', function() {

        var testScope;

        function setTestScope() {
            testScope = this;
        }

        var scopeObject = {
            setTestScope: setTestScope
        };

        var store = Ext.create('Ext.data.Store', {
            fields: ['x', 'y'],
            data: [
                { x: 0, y: 0 },
                { x: 1, y: 1 }
            ]
        });

        var seriesConfig = {
            type: 'bar',
            xField: 'x',
            yField: 'y'
        };

        function createContainer(options) {
            var config = {
                width: 400,
                height: 400,
                layout: 'fit'
            };

            Ext.apply(config, options);

            var container = Ext.create('Ext.container.Container', config);

            container.setTestScope = setTestScope;

            return container;
        }

        function createController() {
            return Ext.create('Ext.app.ViewController', {
                setTestScope: setTestScope
            });
        }

        function createChart(options) {
            var config = {
                store: store,
                series: seriesConfig
            };

            Ext.apply(config, options);

            var chart = Ext.create('Ext.chart.CartesianChart', config);

            chart.setTestScope = setTestScope;

            return chart;
        }

        function createSeriesClass(listenerScope) {
            return Ext.define(null, {
                extend: 'Ext.chart.series.Bar',
                xField: 'x',
                yField: 'y',
                setTestScope: setTestScope,
                listeners: {
                    test: {
                        fn: 'setTestScope',
                        scope: listenerScope
                    }
                }
            });
        }

        describe('series instance listener', function() {

            describe('no chart controller, chart container controller', function() {
                var chart, series,
                    container, containerController;

                beforeEach(function() {
                    testScope = undefined;
                    containerController = createController();
                    chart = createChart();
                    container = createContainer({
                        controller: containerController
                    });
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart container controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(containerController);
                });

                it("listener with no explicit scope should be scoped to chart container controller", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(containerController);
                });
            });

            describe('chart controller, no chart container controller', function() {
                var chart, series,
                    container, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController
                    });
                    container = createContainer();
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart controller, chart container controller', function() {
                var chart, container, series,
                    chartController,
                    containerController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    containerController = createController();
                    chart = createChart({
                        controller: chartController
                    });
                    container = createContainer({
                        controller: containerController
                    });
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('no chart controller, no chart container controller', function() {
                var chart, series, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart();
                    container = createContainer();
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    expect(function() {
                        series.fireEvent('test', series);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart inside container with defaultListenerScope: true (no controllers)', function() {
                var chart, series, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart();
                    container = createContainer({
                        defaultListenerScope: true
                    });
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    expect(function() {
                        series.fireEvent('test', series);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the container", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(container);
                });
            });

            describe('chart with a controller and defaultListenerScope: true', function() {
                var chart, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController,
                        defaultListenerScope: true
                    });
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart with a controller (no container)', function() {
                var chart, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController
                    });
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to the chart controller", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart with defaultListenerScope: true (container, no controllers)', function() {
                var chart, container, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart({
                        defaultListenerScope: true
                    });
                    container = createContainer();
                    container.add(chart);
                    series = chart.getSeries()[0];
                    series.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    series.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    expect(function() {
                        series.fireEvent('test', series);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    series.on('test', 'setTestScope');
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chart);
                });
            });

        });

        // #######################################################################################

        describe('series class listener', function() {

            describe('no chart controller, chart container controller', function() {
                var chart, series,
                    container, containerController;

                beforeEach(function() {
                    testScope = undefined;
                    containerController = createController();
                    chart = createChart({
                        series: []
                    });
                    container = createContainer({
                        controller: containerController
                    });
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart container controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(containerController);
                });

                it("listener with no explicit scope should be scoped to chart container controller", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(containerController);
                });
            });

            describe('chart controller, no chart container controller', function() {
                var chart, series,
                    container, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        series: [],
                        controller: chartController
                    });
                    container = createContainer();
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart controller, chart container controller', function() {
                var chart, container, series,
                    chartController,
                    containerController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    containerController = createController();
                    chart = createChart({
                        series: [],
                        controller: chartController
                    });
                    container = createContainer({
                        controller: containerController
                    });
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('no chart controller, no chart container controller', function() {
                var chart, series, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart({
                        series: []
                    });
                    container = createContainer();
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    expect(function() {
                        series.fireEvent('test', series);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the series", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });
            });

            describe('chart inside container with defaultListenerScope: true (no controllers)', function() {
                var chart, series, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart({
                        series: []
                    });
                    container = createContainer({
                        defaultListenerScope: true
                    });
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    expect(function() {
                        series.fireEvent('test', series);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to chart container", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(container);
                });
            });

            describe('chart with a controller and defaultListenerScope: true', function() {
                var chart, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        series: [],
                        controller: chartController,
                        defaultListenerScope: true
                    });
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart with a controller (no container)', function() {
                var chart, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        series: [],
                        controller: chartController
                    });
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart with defaultListenerScope: true (container, no controllers)', function() {
                var chart, container, series, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        series: [],
                        controller: chartController,
                        defaultListenerScope: true
                    });
                    container = createContainer();
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the series", function() {
                    series = new (createSeriesClass('this'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(series);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    series = new (createSeriesClass(scopeObject))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    series = new (createSeriesClass('controller'))();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart", function() {
                    series = new (createSeriesClass())();
                    chart.setSeries(series);
                    series.fireEvent('test', series);
                    expect(testScope).toBe(chart);
                });
            });

        });

    });

    describe('coordinate', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should update series range as more related series get coordinated', function() {
            // The issue with this was that when multiple series bound to the same
            // axis were coordinated in the axis direction, the range of each series
            // was set consecutively, without accounting for the fact that sebsequent
            // series might affect that range.
            // For example, if the first series has a range of data of [1, 10],
            // and the second has a range of data of [8, 20]. The first will
            // receive [1, 10] as its range (from the axis.getRange()) and the second
            // [8, 20]. While both should receive [1, 20], as this is what the actual
            // final range of the axis will be. But the final range is simly not known
            // at the time when just the first series has been coordinated. So the range
            // of all series bound to an axis should be updated every time we recalculate
            // the axis' range.

            chart = new Ext.chart.PolarChart({
                animation: false,
                renderTo: document.body,
                width: 400,
                height: 400,
                store: {
                    data: [
                        { cat: 'A', inner: 1,  outer: 8 },
                        { cat: 'B', inner: 3,  outer: 10 },
                        { cat: 'C', inner: 10, outer: 20 }
                    ]
                },
                legend: {
                    position: 'right'
                },
                insetPadding: '40 40 60 40',
                interactions: ['rotate'],
                axes: [{
                    type: 'numeric',
                    position: 'radial',
                    grid: true,
                    label: {
                        display: true
                    }
                }, {
                    type: 'category',
                    position: 'angular',
                    grid: true
                }],
                series: [
                    {
                        type: 'radar',
                        angleField: 'cat',
                        radiusField: 'inner'
                    },
                    {
                        type: 'radar',
                        angleField: 'cat',
                        radiusField: 'outer'
                    }
                ]
            });

            var layoutDone;

            var originalLayout = chart.performLayout;

            chart.performLayout = function() {
                originalLayout.call(this);
                layoutDone = true;
            };

            // waitsForSpy fails here for whatever reason, so spying manually
            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                var series = chart.getSeries(),
                    inner = series[0],
                    outer = series[1],
                    sprites, i, ln,
                    expectedYRange = [1, 20],
                    yRange;

                sprites = inner.getSprites();

                for (i = 0, ln = sprites.length; i < ln; i++) {
                    yRange = sprites[i].attr.rangeY;
                    expect(yRange[0]).toBe(expectedYRange[0]);
                    expect(yRange[1]).toBe(expectedYRange[1]);
                }

                sprites = outer.getSprites();

                for (i = 0, ln = sprites.length; i < ln; i++) {
                    yRange = sprites[i].attr.rangeY;
                    expect(yRange[0]).toBe(expectedYRange[0]);
                    expect(yRange[1]).toBe(expectedYRange[1]);
                }
            });
        });

    });

    describe('coordinateData', function() {
        it("should handle empty strings as valid discrete axis values", function() {
            var originalMethod = proto.coordinateData,
                data;

            proto.coordinateData = function(items, field, axis) {
                var result = originalMethod.apply(this, arguments);

                if (field === 'xfield') {
                    data = result;
                }

                return result;
            };

            Ext.create('Ext.chart.CartesianChart', {
                store: {
                    fields: ['xfield', 'a', 'b', 'c'],
                    data: [{
                        xfield: '',
                        a: 10,
                        b: 20,
                        c: 30
                    }]
                },
                axes: [{
                    type: 'numeric',
                    position: 'left',
                    fields: ['a', 'b', 'c']
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: {
                    type: 'bar',
                    stacked: true,
                    xField: 'xfield',
                    yField: ['a', 'b', 'c']
                }
            }).destroy();
            proto.coordinateData = originalMethod;

            expect(data).toEqual([0]);
        });
    });

    describe('updateChart', function() {
        it("should remove sprites from the old chart, destroying them", function() {
            var chart = new Ext.chart.CartesianChart({
                store: {
                    fields: ['xfield', 'a', 'b', 'c'],
                    data: [{
                        xfield: 'A',
                        a: 10,
                        b: 20,
                        c: 30
                    }, {
                        xfield: 'B',
                        a: 30,
                        b: 20,
                        c: 10
                    }]
                },
                axes: [{
                    type: 'numeric',
                    position: 'left',
                    fields: ['a', 'b', 'c']
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: {
                    type: 'bar',
                    stacked: true,
                    xField: 'xfield',
                    yField: ['a', 'b', 'c']
                }
            });

            // Series create 3 bar series sprites (Ext.chart.series.sprite.Bar - marker holder)
            // and 3 marker sprites (Ext.chart.Markers) with 'rect' sprite as a template.
            // So 6 sprites total are in the 'series' surface. The 'rect' sprite templates belong
            // to the markers themselves.
            // This actually checks MarkerHolder's 'destroy' method as well.

            var series = chart.getSeries()[0];

            series.setChart(null);
            expect(chart.getSurface('series').getItems().length).toBe(0);

            chart.destroy();
        });
    });

    describe('showMarkers config', function() {
        var chart, series;

        beforeEach(function() {
            chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: 300,
                height: 200,
                innerPadding: 10,
                animation: false,
                store: {
                    fields: ['x', 'y1', 'y2'],
                    data: [
                        {
                            x: 0,
                            y1: 1,
                            y2: 2
                        },
                        {
                            x: 1,
                            y1: 5,
                            y2: 4
                        },
                        {
                            x: 2,
                            y1: 2,
                            y2: 3
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
                    type: 'line',
                    xField: 'x',
                    yField: 'y1',
                    marker: {
                        type: 'square'
                    },
                    showMarkers: false
                }, {
                    type: 'line',
                    xField: 'x',
                    yField: 'y2',
                    marker: {
                        type: 'arrow'
                    },
                    showMarkers: true
                }]
            });
            chart.performLayout();
            series = chart.getSeries();
        });

        afterEach(function() {
            series = chart = Ext.destroy(chart);
        });

        it("should work with initial value of 'false'", function() {
            var sprite = series[0].getSprites()[0],
                markers = sprite.getMarker('markers'),
                template = markers.getTemplate();

            expect(template.attr.hidden).toBe(true);
        });
        it("should toggle properly from false to true", function() {
            var seriesItem = series[0],
                sprite = seriesItem.getSprites()[0],
                markers = sprite.getMarker('markers'),
                template = markers.getTemplate();

            expect(template.attr.hidden).toBe(true);
            seriesItem.setShowMarkers(true);
            expect(template.attr.hidden).toBe(false);
        });
        it("should toggle properly from true to false", function() {
            var seriesItem = series[1],
                sprite = seriesItem.getSprites()[0],
                markers = sprite.getMarker('markers'),
                template = markers.getTemplate();

            expect(template.attr.hidden).toBe(false);
            seriesItem.setShowMarkers(false);
            expect(template.attr.hidden).toBe(true);
        });
        it("should remain 'false' after series itself are hidden and shown again", function() {
            var seriesItem = series[0],
                sprite = seriesItem.getSprites()[0],
                markers = sprite.getMarker('markers'),
                template = markers.getTemplate();

            expect(template.attr.hidden).toBe(true);
            seriesItem.setHiddenByIndex(0, true);
            seriesItem.setHiddenByIndex(0, false);
            expect(template.attr.hidden).toBe(true);
        });

        it("should remain 'true' after series itself are hidden and shown again", function() {
            var seriesItem = series[1],
                sprite = seriesItem.getSprites()[0],
                markers = sprite.getMarker('markers'),
                template = markers.getTemplate();

            expect(template.attr.hidden).toBe(false);
            seriesItem.setHiddenByIndex(0, true);
            seriesItem.setHiddenByIndex(0, false);
            expect(template.attr.hidden).toBe(false);
        });

    });

    describe("renderer", function() {
        var chart,
            layoutDone,
            fieldMap;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("sprite field names should be set correctly", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',

                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,

                    store: {
                        data: [
                            { x: 1, y1: 1, y2: 4 },
                            { x: 2, y1: 2, y2: 5 },
                            { x: 3, y1: 3, y2: 6 }
                        ]
                    },
                    series: [{
                        type: 'bar',
                        xField: 'x',
                        yField: ['y1', 'y2'],

                        renderer: function(sprite, config, data, index) {
                            var seriesFields = fieldMap.series || (fieldMap.series = []);

                            seriesFields.push(sprite.getField());
                        },

                        label: {
                            field: 'y2',
                            renderer: function(text, sprite, config, data, index) {
                                var labelFields = fieldMap.labels || (fieldMap.labels = []);

                                labelFields.push(sprite.getTemplate().getField());

                                expect(!!sprite.get(index)).toBe(true);
                            }
                        }
                    }],

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

                    listeners: {
                        beforelayout: function() {
                            fieldMap = {};
                        },
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
                var uniqueSeriesFields = Ext.Array.unique(fieldMap.series);

                var uniqueLabelFields = Ext.Array.unique(fieldMap.labels);

                expect(uniqueLabelFields.length).toBe(1);
                expect(uniqueLabelFields[0]).toBe('y2');

                expect(uniqueSeriesFields.length).toBe(2);
            });
        });
    });

});
