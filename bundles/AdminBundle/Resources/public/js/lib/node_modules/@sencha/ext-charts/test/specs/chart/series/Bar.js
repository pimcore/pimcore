topSuite("Ext.chart.series.Bar", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe("x axis range", function() {
        var chart, layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
            layoutDone = false;
        });

        it("should be expanded on both sides by half bar width in case of two bars", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    renderTo: document.body,
                    width: 300,
                    height: 200,
                    store: {
                        data: [{
                            name: 'one',
                            value: 1
                        }, {
                            name: 'two',
                            value: 2
                        }]
                    },
                    axes: [{
                        type: 'numeric',
                        position: 'left'
                    }, {
                        type: 'category',
                        position: 'bottom'
                    }],
                    series: {
                        type: 'bar',
                        xField: 'name',
                        yField: 'value'
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
                var range = chart.getAxes()[1].getRange();

                // Original range of [0, 1] is expanded to fit the left side
                // of the left bar and the right side of the right bar.
                expect(range[0]).toBe(-0.5);
                expect(range[1]).toBe(1.5);
            });
        });

        it("should be expanded on both sides by half bar width in case of multiple bars", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    renderTo: document.body,
                    width: 300,
                    height: 200,
                    store: {
                        data: [{
                            name: 'one',
                            value: 1
                        }, {
                            name: 'two',
                            value: 2
                        }, {
                            name: 'three',
                            value: 3
                        }]
                    },
                    axes: [{
                        type: 'numeric',
                        position: 'left'
                    }, {
                        type: 'category',
                        position: 'bottom'
                    }],
                    series: {
                        type: 'bar',
                        xField: 'name',
                        yField: 'value'
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
                var range = chart.getAxes()[1].getRange();

                // Original range of [0, 1] is expanded to fit the left side
                // of the left bar and the right side of the right bar.
                expect(range[0]).toBe(-0.5);
                expect(range[1]).toBe(2.5);
            });
        });

        it("should not be expanded in case of a single bar", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    renderTo: document.body,
                    width: 300,
                    height: 200,
                    store: {
                        data: [{
                            name: 'one',
                            value: 1
                        }]
                    },
                    axes: [{
                        type: 'numeric',
                        position: 'left'
                    }, {
                        type: 'category',
                        position: 'bottom'
                    }],
                    series: {
                        type: 'bar',
                        xField: 'name',
                        yField: 'value'
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
                var range = chart.getAxes()[1].getRange();

                // Original range of [0, 1] is expanded to fit the left side
                // of the left bar and the right side of the right bar.
                expect(range[0]).toBe(-0.5);
                expect(range[1]).toBe(0.5);
            });
        });
    });
});
