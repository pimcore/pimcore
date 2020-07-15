topSuite("Ext.chart.series.Pie3D", ['Ext.chart.*', 'Ext.data.ArrayStore'], function() {
    describe('radius', function() {
        var chart,
            layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it('should not be negative', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    width: 1,
                    height: 1,
                    renderTo: Ext.getBody(),
                    store: {
                        data: [
                            { x: 1 },
                            { x: 2 },
                            { x: 3 }
                        ]
                    },
                    series: [{
                        type: 'pie3d',
                        angleField: 'x'
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
                expect(chart.getSeries()[0].getRadius() >= 0).toBe(true);
            });
        });
    });

    describe("renderer", function() {
        var chart,
            red = '#ff0000',
            layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("should change slice colors", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',

                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,

                    store: {
                        data: [
                            { x: 1 },
                            { x: 2 },
                            { x: 3 }
                        ]
                    },
                    series: [{
                        type: 'pie',
                        angleField: 'x',
                        renderer: function() {
                            return {
                                fillStyle: red
                            };
                        }
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
                 var series = chart.getSeries()[0];

                 var sprites = series.getSprites();

                 for (var i = 0; i < sprites.length; i++) {
                     var sprite = sprites[i];

                     expect(sprite.attr.fillStyle).toBe(red);
                 }
            });
        });
    });

    describe('dynamic configuration of visual style', function() {
        var chart, layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it('should update the chart when configs affecting visual style change', function() {
            runs(function() {
                chart = new Ext.chart.PolarChart({
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        fields: ['x'],
                        data: [
                            {
                                x: 1, label: 'One'
                            },
                            {
                                x: 2, label: 'Two'
                            }
                        ]
                    },
                    animation: false,
                    series: {
                        type: 'pie3d',
                        angleField: 'x'
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
                layoutDone = false;

                var series = chart.getSeries()[0],
                    sprites = series.getSprites(),
                    thickness = series.getThickness(),
                    distortion = series.getDistortion(),
                    i, sprite;

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.thickness).toBe(thickness);
                    expect(sprite.attr.distortion).toBe(distortion);
                    expect(sprite.attr.startRho).toBe(0);
                    expect(sprite.attr.centerX).toBe(190);
                    expect(sprite.attr.centerY).toBe(172.5);
                }

                expect(chart.getInnerPadding()).toBe(0);

                series.setThickness(20);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    expect(sprites[i].attr.thickness).toBe(20);
                }

                series.setDistortion(0.7);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    expect(sprites[i].attr.distortion).toBe(0.7);
                }

                series.setDonut(40);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    expect(sprites[i].attr.startRho).toBe(76);
                }

                chart.setInnerPadding(50);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var series = chart.getSeries()[0],
                    sprites = series.getSprites(),
                    i, sprite;

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.startRho).toBe(56);
                    expect(sprite.attr.centerX).toBe(190);
                    expect(sprite.attr.centerY).toBe(180);
                }

                var oldColor = sprites[0].attr.baseColor,
                    newColor = Ext.chart.theme.Midnight.getColors()[0];

                chart.setTheme('Midnight');

                expect(sprites[0].attr.baseColor).not.toBe(oldColor);
                expect(sprites[0].attr.baseColor).toBe(newColor);

                chart.setTheme('Default');

                expect(sprites[0].attr.baseColor).not.toBe(newColor);
                expect(sprites[0].attr.baseColor).toBe(oldColor);

                series.setOffsetX(30);
                series.setOffsetY(30);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.centerX).toBe(220);
                    expect(sprite.attr.centerY).toBe(210);
                }

                series.setOffsetX(0);
                series.setOffsetY(0);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.centerX).toBe(190);
                    expect(sprite.attr.centerY).toBe(180);
                }

                series.setCenter([150, 150]);
                chart.redraw();

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.centerX).toBe(150);
                    expect(sprite.attr.centerY).toBe(140);
                }

                // Layout will reset the center (which is not supposed to be set manually).
                chart.performLayout();
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var series = chart.getSeries()[0],
                    sprites = series.getSprites(),
                    i, sprite;

                for (i = 0; i < sprites.length; i++) {
                    sprite = sprites[i];
                    expect(sprite.attr.centerX).toBe(190);
                    expect(sprite.attr.centerY).toBe(180);
                }

                var colors = [
                    '#ffff00',
                    '#00ff00',
                    '#ff0000'
                ];

                series.setRenderer(function(sprite, config, data, index) {
                    return {
                        fillStyle: colors[index % 3]
                    };
                });
                chart.redraw();

                var perSlice = Ext.chart.series.Pie3D.prototype.spritesPerSlice,
                    n = sprites.length / perSlice;

                for (i = 0; i < n; i++) {
                    expect(sprites[i * perSlice].attr.fillStyle).toBe(colors[i]);
                }

                chart.getStore().add({
                    x: 3, label: 'Three'
                });

                n = sprites.length / perSlice;

                // Existing renderer should apply to the newly created sprite
                // for the added data point.
                for (i = 0; i < n; i++) {
                    expect(sprites[i * perSlice].attr.fillStyle).toBe(colors[i]);
                }

                series.setLabel({
                    field: 'x'
                });

                var labelInstances = series.getLabel().instances;

                // The label Ext.chart.Markers should be put into the 'overlay' surface
                // when it is created as a result of the 'setLabel' call above (otherwise,
                // the labels won't render).
                expect(series.getLabel().getSurface()).toBe(series.getOverlaySurface());

                expect(labelInstances[0].text).toBe("1");
                expect(labelInstances[1].text).toBe("2");
                expect(labelInstances[2].text).toBe("3");

                series.setLabel({
                    field: 'label'
                });

                var oldLabel = series.getLabel();

                labelInstances = oldLabel.instances;

                expect(labelInstances[0].text).toBe("One");
                expect(labelInstances[1].text).toBe("Two");
                expect(labelInstances[2].text).toBe("Three");

                series.setLabel(null);

                expect(series.getLabel()).toBe(null);
                expect(oldLabel.isDestroyed).toBe(true);

                expect(series.getSprites()[0].modifiers.highlight).toBeFalsy();
                series.setHighlight(true);
                expect(series.getSprites()[0].modifiers.highlight).toBeTruthy();
            });
        });
    });

    describe('data changes', function() {
        var chart, layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it('should update the series sprites when data changes', function() {
            runs(function() {
                chart = new Ext.chart.PolarChart({
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        fields: ['x']
                    },
                    animation: false,
                    legend: true,
                    series: {
                        type: 'pie3d',
                        angleField: 'x'
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
                layoutDone = false;

                var sprites = chart.getSeries()[0].getSprites();

                var legendSprites = chart.getLegend().getSprites();

                expect(sprites.length).toBe(0);
                expect(legendSprites.length).toBe(0);

                chart.getStore().setData([
                    {
                        x: 1, label: 'One'
                    }
                ]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var sprites = chart.getSeries()[0].getSprites();

                var legendSprites = chart.getLegend().getSprites();

                expect(sprites.length).toBe(Ext.chart.series.Pie3D.prototype.spritesPerSlice);
                expect(legendSprites.length).toBe(1);

                chart.getStore().setData([
                    {
                        x: 1, label: 'One'
                    },
                    {
                        x: 2, label: 'Two'
                    }
                ]);
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                layoutDone = false;

                var sprites = chart.getSeries()[0].getSprites();

                var legendSprites = chart.getLegend().getSprites();

                expect(sprites.length).toBe(Ext.chart.series.Pie3D.prototype.spritesPerSlice * 2);
                expect(legendSprites.length).toBe(2);
            });
        });

    });

    describe("label.renderer", function() {
        var chart,
            labelText = 'xd',
            layoutDone;

        afterEach(function() {
            chart = Ext.destroy(chart);
        });

        it("should change slice labels", function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',

                    renderTo: Ext.getBody(),
                    width: 400,
                    height: 400,

                    store: {
                        data: [
                            { x: 1 },
                            { x: 2 },
                            { x: 3 }
                        ]
                    },
                    series: [{
                        type: 'pie',
                        angleField: 'x',
                        label: {
                            field: 'x',
                            renderer: function() {
                                return labelText;
                            }
                        }
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
                var series = chart.getSeries()[0];

                var sprites = series.getSprites();

                for (var i = 0; i < sprites.length; i++) {
                    var sprite = sprites[i];

                    expect(sprite.getMarker('labels').get(0).text).toBe('xd');
                }
            });
        });
    });
});
