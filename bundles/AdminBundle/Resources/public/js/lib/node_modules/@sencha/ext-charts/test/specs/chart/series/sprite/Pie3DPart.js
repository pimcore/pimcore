topSuite("Ext.chart.series.sprite.Pie3DPart", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    beforeEach(function() {
        // Silence warnings regarding Sencha download server
        spyOn(Ext.log, 'warn');
    });

    describe("series 'opacity' style change", function() {
        it("should result in a corresponding sprite attribute change", function() {
            // The change should propagate to sprite' attributes and result
            // in a change to sprite's path (if that sprite represents
            // a part that's only visible when a slice is translucent).
            var chart = new Ext.chart.PolarChart({
                reference: 'chart',
                animation: false,
                renderTo: document.body,
                width: 200,
                height: 200,
                store: {
                    fields: ['os', 'data1', 'data2' ],
                    data: [
                        { os: 'Android', data1: 68.3, data2: 150 },
                        { os: 'iOS', data1: 17.9, data2: 200 },
                        { os: 'Windows Phone', data1: 10.2, data2: 250 },
                        { os: 'BlackBerry', data1: 1.7, data2: 90 },
                        { os: 'Others', data1: 1.9, data2: 190 }
                    ]
                },
                series: {
                    type: 'pie3d',
                    angleField: 'data1',
                    donut: 30,
                    distortion: 0.6
                }
            });

            var series = chart.getSeries()[0];

            var sprites = series.getSprites();

            var sprite, i, ln;

            for (i = 0, ln = sprites.length; i < ln; i++) {
                sprite = sprites[i];

                if (sprite.attr.part === 'bottom') {
                    // The bottom sprite is not only not rendered when a slice
                    // is completely opaque, but it doesn't even have a path
                    // calculated for it, as a performance optimization.
                    expect(sprite.attr.path.params.length).toBe(0);
                }
            }

            series.setStyle({
                opacity: 0.8 // converted to 'globalAlpha' during attribute normalization
            });

            for (i = 0, ln = sprites.length; i < ln; i++) {
                sprite = sprites[i];
                expect(sprite.attr.globalAlpha).toEqual(0.8);

                if (sprite.attr.part === 'bottom') {
                    // The path for a normally invisible sprite should be created.
                    expect(sprite.attr.path.params.length).toBeGreaterThan(0);
                }
            }

            chart.destroy();
        });
    });

    describe("series 'fillOpacity' style change", function() {
        it("should result in a corresponding sprite attribute change", function() {
            // The change should propagate to sprite' attributes and result
            // in a change to sprite's path (if that sprite represents
            // a part that's only visible when a slice is translucent).
            var chart = new Ext.chart.PolarChart({
                reference: 'chart',
                animation: false,
                renderTo: document.body,
                width: 200,
                height: 200,
                store: {
                    fields: ['os', 'data1', 'data2' ],
                    data: [
                        { os: 'Android', data1: 68.3, data2: 150 },
                        { os: 'iOS', data1: 17.9, data2: 200 },
                        { os: 'Windows Phone', data1: 10.2, data2: 250 },
                        { os: 'BlackBerry', data1: 1.7, data2: 90 },
                        { os: 'Others', data1: 1.9, data2: 190 }
                    ]
                },
                series: {
                    type: 'pie3d',
                    angleField: 'data1',
                    donut: 30,
                    distortion: 0.6
                }
            });

            var series = chart.getSeries()[0];

            var sprites = series.getSprites();

            var sprite, i, ln;

            for (i = 0, ln = sprites.length; i < ln; i++) {
                sprite = sprites[i];

                if (sprite.attr.part === 'bottom') {
                    // The bottom sprite is not only not rendered when a slice
                    // is completely opaque, but it doesn't even have a path
                    // calculated for it, as a performance optimization.
                    expect(sprite.attr.path.params.length).toBe(0);
                }
            }

            series.setStyle({
                fillOpacity: 0.8 // converted to 'globalAlpha' during attribute normalization
            });

            for (i = 0, ln = sprites.length; i < ln; i++) {
                sprite = sprites[i];
                expect(sprite.attr.fillOpacity).toEqual(0.8);

                if (sprite.attr.part === 'bottom') {
                    // The path for a normally invisible sprite should be created.
                    expect(sprite.attr.path.params.length).toBeGreaterThan(0);
                }
            }

            chart.destroy();
        });
    });

    describe("renderer", function() {
        var chart;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("should be called with a proper slice index", function() {
            var layoutDone;

            runs(function() {
                chart = new Ext.chart.PolarChart({
                    animation: false,
                    renderTo: document.body,
                    width: 200,
                    height: 200,
                    store: {
                        fields: ['x'],
                        data: [
                            { x: 1 },
                            { x: 2 },
                            { x: 3 }
                        ]
                    },
                    series: {
                        type: 'pie3d',
                        angleField: 'x',
                        renderer: function(sprite, config, data, index) {
                            var delta = sprite.attr.endAngle - sprite.attr.startAngle;

                            expect(delta).toBeCloseTo(Math.PI * 2 / 6 * data.store.getAt(index).get(data.angleField), 8);

                            return {};
                        }
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
        });
    });
});
