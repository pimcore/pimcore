topSuite("Ext.chart.AbstractChart.modern",
    [false, 'Ext.chart.*', 'Ext.data.ArrayStore'],
function() {
    var chart;

    afterEach(function() {
        chart = Ext.destroy(chart);
    });

    describe('background', function() {
        var panel;

        beforeEach(function() {
            panel = Ext.create({
                xtype: 'panel',
                width: 400,
                height: 400,
                layout: 'fit',
                renderTo: document.body,
                style: 'background: red;'
            });
        });

        afterEach(function() {
            panel = Ext.destroy(panel);
        });

        it("should be white after layout end", function() {
            var layoutDone;

            runs(function() {
                chart = new Ext.chart.CartesianChart({
                    engine: 'Ext.draw.engine.Canvas',
                    store: {
                        data: [
                            {
                                name: 'item-1',
                                value: 1
                            },
                            {
                                name: 'item-2',
                                value: 3
                            },
                            {
                                name: 'item-3',
                                value: 2
                            }
                        ]
                    },
                    series: {
                        type: 'line',
                        xField: 'name',
                        yField: 'value'
                    },
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
                    legend: {
                        type: 'sprite'
                    },
                    listeners: {
                        layout: function() {
                            layoutDone = true;
                        }
                    }
                });
                panel.setItems([chart]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // Fetch the first pixel and check if it's white.
                // That should be enough
                var imageData = chart.getSurface('background').contexts[0].getImageData(0, 0, 1, 1);

                var data = imageData.data;

                for (var i = 0; i < data.length; i++) {
                    expect(data[i]).toBe(255);
                }
            });
        });
    });
});
