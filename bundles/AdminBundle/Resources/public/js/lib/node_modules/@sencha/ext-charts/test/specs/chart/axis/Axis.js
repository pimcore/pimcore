topSuite("Ext.chart.axis.Axis",
    ['Ext.chart.*', 'Ext.data.ArrayStore', 'Ext.app.ViewController',
     'Ext.Container', 'Ext.layout.Fit'],
function() {
    beforeEach(function() {
        // Tons of warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe('getRange', function() {
        it("linked axes should always return the range of the master axis", function() {
            var chartConfig = {
                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 200,
                    store: {
                        fields: ['name', 'value'],
                        data: [{
                            name: 'one',
                            value: 10
                        }, {
                            name: 'two',
                            value: 7
                        }, {
                            name: 'three',
                            value: 5
                        }, {
                            name: 'four',
                            value: 2
                        }, {
                            name: 'five',
                            value: 27
                        }]
                    },
                    series: {
                        type: 'bar',
                        xField: 'name',
                        yField: 'value'
                    }
                },
                verticalNumeric = Ext.Object.merge({}, chartConfig, {
                    axes: [
                        {
                            id: 'left',
                            type: 'numeric',
                            position: 'left'
                        },
                        {
                            id: 'bottom',
                            type: 'category',
                            position: 'bottom'
                        },
                        {
                            position: 'right',
                            linkedTo: 'left'
                        },
                        {
                            position: 'top',
                            linkedTo: 'bottom'
                        }
                    ]
                }),
                horizontalNumeric = Ext.Object.merge({}, chartConfig, {
                    flipXY: true,
                    axes: [
                        {
                            id: 'left',
                            type: 'category',
                            position: 'left'
                        },
                        {
                            id: 'bottom',
                            type: 'numeric',
                            position: 'bottom'
                        },
                        {
                            position: 'top',
                            linkedTo: 'bottom'
                        },
                        {
                            position: 'right',
                            linkedTo: 'left'
                        }
                    ]
                });

            var axisProto = Ext.chart.axis.Axis.prototype,
                originalGetRange = axisProto.getRange;

            function getRange() {
                var range = originalGetRange.apply(this, arguments),
                    masterAxis = this.masterAxis;

                if (range && masterAxis) {
                    expect(range[0]).toEqual(masterAxis.range[0]);
                    expect(range[1]).toEqual(masterAxis.range[1]);
                }

                return range;
            }

            axisProto.getRange = getRange;

            var verticalNumericChart = new Ext.chart.CartesianChart(verticalNumeric);

            verticalNumericChart.performLayout();
            verticalNumericChart.destroy();

            var horizontalNumericChart = new Ext.chart.CartesianChart(horizontalNumeric);

            horizontalNumericChart.performLayout();
            horizontalNumericChart.destroy();

            axisProto.getRange = originalGetRange;
        });
    });

    describe('adjustByMajorUnit', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should round the axis range to nice values', function() {
            var layoutDone;

            chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: 400,
                height: 400,
                store: {
                    data: [
                        { year: 1890, men: 1002, women: 988 },
                        { year: 1900, men: 1007, women: 999 },
                        { year: 1910, men: 1056, women: 1043 },
                        { year: 1920, men: 1077, women: 1044 },
                        { year: 1930, men: 1099, women: 1082 },
                        { year: 1940, men: 1125, women: 1098 },
                        { year: 1950, men: 885,  women: 1076 }
                    ]
                },
                axes: [{
                    id: 'left',
                    type: 'numeric',
                    position: 'left',
                    grid: true
                }, {
                    type: 'category',
                    position: 'bottom'
                }],
                series: [{
                    stacked: false,
                    type: 'bar',
                    xField: 'year',
                    yField: ['men', 'women']
                }],
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                }
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                var leftAxis = chart.getAxis('left'),
                    attr = leftAxis.getSprites()[0].attr;

                expect(attr.max).toBe(1200);
                expect(attr.dataMax).toBe(1200); // TODO: One would expect this to be 1125.
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

        var axisConfig = {
            type: 'numeric',
            position: 'bottom'
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
                axes: axisConfig
            };

            Ext.apply(config, options);

            var chart = Ext.create('Ext.chart.CartesianChart', config);

            chart.setTestScope = setTestScope;

            return chart;
        }

        function createAxisClass(listenerScope) {
            return Ext.define(null, {
                extend: 'Ext.chart.axis.Numeric',
                setTestScope: setTestScope,
                listeners: {
                    test: {
                        fn: 'setTestScope',
                        scope: listenerScope
                    }
                }
            });
        }

        describe('axis instance listener', function() {

            describe('no chart controller, chart container controller', function() {
                var chart, axis,
                    container, containerController;

                beforeEach(function() {
                    testScope = undefined;
                    containerController = createController();
                    chart = createChart();
                    container = createContainer({
                        controller: containerController
                    });
                    container.add(chart);
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart container controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(containerController);
                });

                it("listener with no explicit scope should be scoped to chart container controller", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(containerController);
                });
            });

            describe('chart controller, no chart container controller', function() {
                var chart, axis,
                    container, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController
                    });
                    container = createContainer();
                    container.add(chart);
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart controller, chart container controller', function() {
                var chart, container, axis,
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
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('no chart controller, no chart container controller', function() {
                var chart, axis, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart();
                    container = createContainer();
                    container.add(chart);
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    expect(function() {
                        axis.fireEvent('test', axis);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart inside container with defaultListenerScope: true (no controllers)', function() {
                var chart, axis, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart();
                    container = createContainer({
                        defaultListenerScope: true
                    });
                    container.add(chart);
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    expect(function() {
                        axis.fireEvent('test', axis);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the container", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(container);
                });
            });

            describe('chart with a controller and defaultListenerScope: true', function() {
                var chart, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController,
                        defaultListenerScope: true
                    });
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart with a controller', function() {
                var chart, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController
                    });
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to the chart controller", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart with defaultListenerScope: true (container, no controllers)', function() {
                var chart, container, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        controller: chartController,
                        defaultListenerScope: true
                    });
                    container = createContainer();
                    container.add(chart);
                    axis = chart.getAxes()[0];
                    axis.setTestScope = setTestScope;
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'this'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: scopeObject
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to the chart controller", function() {
                    axis.on({
                        test: 'setTestScope',
                        scope: 'controller'
                    });
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to the chart", function() {
                    axis.on('test', 'setTestScope');
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chart);
                });
            });

        });

        // #######################################################################################

        describe('axis class listener', function() {

            describe('no chart controller, chart container controller', function() {
                var chart, axis,
                    container, containerController;

                beforeEach(function() {
                    testScope = undefined;
                    containerController = createController();
                    chart = createChart({
                        axes: []
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

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart container controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(containerController);
                });

                it("listener with no explicit scope should be scoped to chart container controller", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(containerController);
                });
            });

            describe('chart controller, no chart container controller', function() {
                var chart, axis,
                    container, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        axes: [],
                        controller: chartController
                    });
                    container = createContainer();
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart controller, chart container controller', function() {
                var chart, container, axis,
                    chartController,
                    containerController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    containerController = createController();
                    chart = createChart({
                        axes: [],
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

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('no chart controller, no chart container controller', function() {
                var chart, axis, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart({
                        axes: []
                    });
                    container = createContainer();
                    container.add(chart);
                });

                afterEach(function() {
                    chart.destroy();
                    container.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    expect(function() {
                        axis.fireEvent('test', axis);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to the axis", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });
            });

            describe('chart inside container with defaultListenerScope: true (no controllers)', function() {
                var chart, axis, container;

                beforeEach(function() {
                    testScope = undefined;
                    chart = createChart({
                        axes: []
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

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should fail", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    expect(function() {
                        axis.fireEvent('test', axis);
                    }).toThrow();
                });

                it("listener with no explicit scope should be scoped to chart container", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(container);
                });
            });

            describe('chart with a controller and defaultListenerScope: true', function() {
                var chart, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        axes: [],
                        controller: chartController,
                        defaultListenerScope: true
                    });
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chart);
                });
            });

            describe('chart with a controller (no container)', function() {
                var chart, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        axes: [],
                        controller: chartController
                    });
                });

                afterEach(function() {
                    chart.destroy();
                });

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart controller", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });
            });

            describe('chart with defaultListenerScope: true (container, no controllers)', function() {
                var chart, container, axis, chartController;

                beforeEach(function() {
                    testScope = undefined;
                    chartController = createController();
                    chart = createChart({
                        axes: [],
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

                it("listener scoped to 'this' should refer to the axis", function() {
                    axis = new (createAxisClass('this'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(axis);
                });

                it("listener scoped to an arbitrary object should refer to that object", function() {
                    axis = new (createAxisClass(scopeObject))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(scopeObject);
                });

                it("listener scoped to 'controller' should refer to chart controller", function() {
                    axis = new (createAxisClass('controller'))();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chartController);
                });

                it("listener with no explicit scope should be scoped to chart", function() {
                    axis = new (createAxisClass())();
                    chart.setAxes(axis);
                    axis.fireEvent('test', axis);
                    expect(testScope).toBe(chart);
                });
            });

        });

    });
});
