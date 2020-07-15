topSuite("Ext.chart.AbstractChart.classic",
    [false, 'Ext.chart.*', 'Ext.data.ArrayStore'],
function() {
    var chart, store;

    var Model = Ext.define(null, {
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

    afterEach(function() {
        store = chart = Ext.destroy(chart, store);
    });

    describe('interactions', function() {
        it("should not be created, unless configured", function() {
            makeStore(2);
            chart = new Ext.chart.PolarChart({
                width: 400,
                height: 400,
                store: store,
                series: {
                    type: 'pie',
                    angleField: 'value'
                }
            });

            expect(chart.getInteractions().length).toEqual(0);
        });
    });

    describe('layout', function() {
        it("should size chart's body to the size of the parent element", function() {
            var value = 400,
                bodySize;

            makeStore(2);

            new Ext.chart.PolarChart({
                width: value,
                height: value,
                store: store,
                renderTo: document.body,
                series: {
                    type: 'pie',
                    angleField: 'value'
                },
                listeners: {
                    afterLayout: function() {
                        bodySize = this.body.getSize();
                    }
                }
            }).destroy();

            expect(bodySize).toBeDefined();
            expect(bodySize.width).toEqual(value);
            expect(bodySize.height).toEqual(value);
        });
    });
});
