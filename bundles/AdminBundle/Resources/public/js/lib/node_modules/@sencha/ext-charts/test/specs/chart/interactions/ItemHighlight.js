topSuite("Ext.chart.interactions.ItemHighlight", ['Ext.chart.*', 'Ext.data.ArrayStore'],
function() {
    var chart;

    afterEach(function() {
        chart = Ext.destroy(chart);
    });

    (jasmine.supportsTouch ? xdescribe : describe)("multiTooltips", function() {
        var layoutSpy, series1Spy, series2Spy, series3Spy, series4Spy,
            tooltips;

        function expectSpies(spy1, spy2, spy3, spy4) {
            if (spy1) {
                expect(series1Spy).toHaveBeenCalled();
            }
            else {
                expect(series1Spy).not.toHaveBeenCalled();
            }

            if (spy2) {
                expect(series2Spy).toHaveBeenCalled();
            }
            else {
                expect(series2Spy).not.toHaveBeenCalled();
            }

            if (spy3) {
                expect(series3Spy).toHaveBeenCalled();
            }
            else {
                expect(series3Spy).not.toHaveBeenCalled();
            }

            if (spy4) {
                expect(series4Spy).toHaveBeenCalled();
            }
            else {
                expect(series4Spy).not.toHaveBeenCalled();
            }
        }

        function resetSpies() {
            series1Spy.reset();
            series2Spy.reset();
            series3Spy.reset();
            series4Spy.reset();
        }

        beforeEach(function() {
            tooltips = [];
            layoutSpy = jasmine.createSpy('layout done');
            series1Spy = jasmine.createSpy('series 1 tooltip renderer');
            series2Spy = jasmine.createSpy('series 2 tooltip renderer');
            series3Spy = jasmine.createSpy('series 3 tooltip renderer');
            series4Spy = jasmine.createSpy('series 4 tooltip renderer');

            chart = Ext.create({
                xtype: 'cartesian',
                renderTo: document.body,
                width: 470,
                height: 300,
                store: {
                    fields: ['name', 'data1', 'data2', 'data3', 'data4'],
                    data: [{
                        'name': 'metric one',
                        'data1': 10,
                        'data2': 14,
                        'data3': 18,
                        'data4': 2
                    }, {
                        'name': 'metric two',
                        'data1': 7,
                        'data2': 16,
                        'data3': 18,
                        'data4': 2
                    }, {
                        'name': 'metric three',
                        'data1': 5,
                        'data2': 14,
                        'data3': 18,
                        'data4': 2
                    }, {
                        'name': 'metric four',
                        'data1': 2,
                        'data2': 6,
                        'data3': 18,
                        'data4': 2
                    }, {
                        'name': 'metric five',
                        'data1': 27,
                        'data2': 36,
                        'data3': 18,
                        'data4': 2
                    }]
                },

                interactions: {
                    type: 'itemhighlight',
                    multiTooltips: true
                },

                series: [{
                    type: 'line',
                    title: 'Series 1',
                    style: {
                        lineWidth: 2,
                        selectionTolerance: 40
                    },
                    xField: 'name',
                    yField: 'data1',
                    marker: {
                        type: 'diamond',
                        path: ['M', -4, 0, 0, 4, 4, 0, 0, -4, 'Z'],
                        lineWidth: 2
                    },
                    tips: {
                        trackMouse: true,
                        renderer: series1Spy
                    }
                }, {
                    type: 'line',
                    title: 'Series 2',
                    style: {
                        lineWidth: 2,
                        selectionTolerance: 40
                    },
                    xField: 'name',
                    yField: 'data2',
                    marker: {
                        type: 'diamond',
                        path: ['M', -4, 0, 0, 4, 4, 0, 0, -4, 'Z'],
                        lineWidth: 2
                    },
                    tips: {
                        trackMouse: true,
                        renderer: series2Spy
                    }
                }, {
                    type: 'line',
                    title: 'Series 3',
                    style: {
                        lineWidth: 2,
                        selectionTolerance: 40
                    },
                    xField: 'name',
                    yField: 'data3',
                    marker: {
                        type: 'diamond',
                        path: ['M', -4, 0, 0, 4, 4, 0, 0, -4, 'Z'],
                        lineWidth: 2
                    },
                    tips: {
                        trackMouse: true,
                        renderer: series3Spy
                    }
                }, {
                    type: 'line',
                    title: 'Series 4',
                    style: {
                        lineWidth: 2,
                        selectionTolerance: 40
                    },
                    xField: 'name',
                    yField: 'data4',
                    marker: {
                        type: 'diamond',
                        path: ['M', -4, 0, 0, 4, 4, 0, 0, -4, 'Z'],
                        lineWidth: 2
                    },
                    tips: {
                        trackMouse: true,
                        renderer: series4Spy
                    }
                }],

                axes: [{
                    type: 'numeric',
                    position: 'left',
                    fields: ['data1', 'data2', 'data3', 'data4'],
                    title: {
                        text: 'Sample Values',
                        fontSize: 15
                    },
                    grid: true,
                    minimum: 0
                }, {
                    type: 'category',
                    position: 'bottom',
                    fields: ['name'],
                    title: {
                        text: 'Sample Values',
                        fontSize: 15
                    }
                }],

                listeners: {
                    layout: layoutSpy
                }
            });

            series1Spy.andCallFake(function(tooltip) {
                tooltips.push(tooltip);
            });

            series2Spy.andCallFake(function(tooltip) {
                tooltips.push(tooltip);
            });

            series3Spy.andCallFake(function(tooltip) {
                tooltips.push(tooltip);
            });

            series4Spy.andCallFake(function(tooltip) {
                tooltips.push(tooltip);
            });

            waitForSpy(layoutSpy);
        });

        afterEach(function() {
            layoutSpy = series1Spy = series2Spy = series3Spy = series4Spy = null;
            tooltips = null;
        });

        describe("multiTooltips === false", function() {
            beforeEach(function() {
                chart.getInteractions()[0].setMultiTooltips(false);
            });

            it("should call showTooltip only for one series", function() {
                jasmine.fireMouseEvent(chart, 'mousemove', 257, 143);

                expectSpies(false, false, true, false);
            });

            it("should reuse tooltip instance when mouse moves within tolerance", function() {
                var tooltip;

                jasmine.fireMouseEvent(chart, 'mousemove', 257, 143);

                expectSpies(false, false, true, false);

                expect(tooltips.length).toBe(1);
                expect(tooltips[0].isVisible()).toBe(true);

                tooltip = tooltips[0];
                tooltips.length = 0;
                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 260, 138);

                expectSpies(false, false, true, false);
                expect(tooltips.length).toBe(1);

                expect(tooltips[0]).toBe(tooltip);
                expect(tooltips[0].isVisible()).toBe(true);
            });

            it("should hide old tooltip when cursor moves outside of tolerance", function() {
                var tooltip;

                jasmine.fireMouseEvent(chart, 'mousemove', 254, 141);

                expectSpies(false, false, true, false);
                expect(tooltips.length).toBe(1);

                tooltip = tooltips[0];
                expect(tooltip.isVisible()).toBe(true);

                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 0, 0);

                waitFor(function() {
                    return !tooltip.isVisible();
                });

                runs(function() {
                    expect(tooltip.isVisible()).toBe(false);
                    expectSpies(false, false, false, false);
                });
            });

            it("should hide old tooltip and show new one", function() {
                var tooltip;

                jasmine.fireMouseEvent(chart, 'mousemove', 140, 240);

                expectSpies(false, false, false, true);
                expect(tooltips.length).toBe(1);

                tooltip = tooltips[0];

                expect(tooltip.isVisible()).toBe(true);

                tooltips.length = 0;
                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 356, 120);

                waitFor(function() {
                    return !tooltip.isVisible();
                });

                runs(function() {
                    expectSpies(false, false, true, false);
                    expect(tooltip.isVisible()).toBe(false);
                    expect(tooltips.length).toBe(1);
                    expect(tooltips[0]).not.toBe(tooltip);
                    expect(tooltips[0].isVisible()).toBe(true);
                });
            });
        });

        describe("multiTooltips === true", function() {
            it("should call showTooltip for multiple series", function() {
                jasmine.fireMouseEvent(chart, 'mousemove', 154, 120);

                expectSpies(false, true, true, false);
                expect(tooltips.length).toBe(2);
            });

            it("should add tooltips when more than one series is within radius", function() {
                jasmine.fireMouseEvent(chart, 'mousemove', 370, 125);

                expectSpies(false, false, true, false);
                expect(tooltips.length).toBe(1);

                tooltips.length = 0;
                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 168, 125);

                expectSpies(false, true, true, false);
                expect(tooltips.length).toBe(2);
            });

            it("should reuse tooltip instances when mouse moves within tolerance", function() {
                var oldTooltips;

                jasmine.fireMouseEvent(chart, 'mousemove', 168, 125);

                expectSpies(false, true, true, false);

                expect(tooltips.length).toBe(2);

                expect(tooltips[0].isVisible()).toBe(true);
                expect(tooltips[1].isVisible()).toBe(true);

                oldTooltips = tooltips;
                tooltips.length = 0;
                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 264, 146);

                expectSpies(false, true, true, false);
                expect(tooltips.length).toBe(2);

                expect(tooltips[0]).toBe(oldTooltips[0]);
                expect(tooltips[1]).toBe(oldTooltips[1]);
            });

            it("should hide old tooltips and show new ones", function() {
                var oldTooltips = [];

                jasmine.fireMouseEvent(chart, 'mousemove', 264, 146);

                expectSpies(false, true, true, false);
                expect(tooltips.length).toBe(2);
                expect(tooltips[0].isVisible()).toBe(true);
                expect(tooltips[1].isVisible()).toBe(true);

                oldTooltips[0] = tooltips[0];
                oldTooltips[1] = tooltips[1];
                tooltips.length = 0;
                resetSpies();

                jasmine.fireMouseEvent(chart, 'mousemove', 147, 222);

                waitFor(function() {
                    return !oldTooltips[0].isVisible() && !oldTooltips[1].isVisible();
                }, 'tooltips to hide', 1000);

                runs(function() {
                    expectSpies(true, false, false, true);
                    expect(tooltips.length).toBe(2);

                    expect(tooltips[0].isVisible()).toBe(true);
                    expect(tooltips[1].isVisible()).toBe(true);

                    expect(Ext.Array.contains(oldTooltips, tooltips[0])).toBe(false);
                    expect(Ext.Array.contains(oldTooltips, tooltips[1])).toBe(false);
                });
            });
        });
    });
});
