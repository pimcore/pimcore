/* global Ext, expect */

topSuite("Ext.chart.legend.SpriteLegend", ['Ext.chart.*', 'Ext.data.ArrayStore', 'Ext.data.JsonStore'], function() {
    function generateStoreData(pointCount) {
        var data = [
                { month: 'Jan' },
                { month: 'Feb' },
                { month: 'Mar' },
                { month: 'Apr' },
                { month: 'May' },
                { month: 'Jun' },
                { month: 'Jul' },
                { month: 'Aug' },
                { month: 'Sep' },
                { month: 'Oct' },
                { month: 'Nov' },
                { month: 'Dec' }
            ],
            i = 0,
            j = 0,
            ln = data.length,
            entry;

        for (; i < ln; i++) {
            entry = data[i];

            for (j = 0; j < pointCount; j++) {
                entry['data' + (j + 1).toString()] = Math.random() * 10;
            }
        }

        return data;
    }

    beforeEach(function() {
        // Silence Sencha download server warnings
        spyOn(Ext.log, 'warn');
    });

    describe('markers', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should be visible even if the series markers are hidden', function() {
            var layoutDone;

            runs(function() {
                chart = new Ext.chart.CartesianChart({
                    renderTo: Ext.getBody(),
                    width: 300,
                    height: 300,
                    store: {
                        data: [
                            { x: 1, y: 1 },
                            { x: 2, y: 3 },
                            { x: 3, y: 1 }
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
                        showMarkers: false,
                        marker: {
                            type: 'square'
                        },
                        type: 'line',
                        xField: 'x',
                        yField: 'y'
                    }],
                    legend: true,
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
                var sprite = chart.getLegend().getSprites()[0];

                expect(sprite.getMarker().attr.hidden).toBe(false);
            });
        });
    });

    describe('docked', function() {
        var chart;

        afterEach(function() {
            Ext.destroy(chart);
        });

        it('should position the sprite legend properly', function() {
            var side = 400,
                layoutDone,
                legendSpriteCount,
                legendSpriteIds;

            chart = new Ext.chart.CartesianChart({
                renderTo: Ext.getBody(),
                width: side,
                height: side,
                store: {
                    data: [
                        { x: 1, y: 1 },
                        { x: 2, y: 3 },
                        { x: 3, y: 1 }
                    ]
                },
                axes: [
                    {
                        type: 'numeric',
                        position: 'left'
                    },
                    {
                        type: 'category',
                        position: 'bottom'
                    }
                ],
                series: {
                    type: 'bar',
                    xField: 'x',
                    yField: 'y'
                },
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
                chart.setLegend({
                    type: 'sprite',
                    docked: 'top'
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'top'
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendSurface = legend.getSurface(),
                    legendSprites = legendSurface.getItems(),
                    legendRect = legendSurface.getRect();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(legendSize.height);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side - legendSize.height);

                expect(legendRect[0]).toBe(0);
                expect(legendRect[1]).toBe(0);
                expect(legendRect[2]).toBe(side);
                expect(legendRect[3]).toBe(legendSize.height);

                legendSpriteCount = legendSprites.length;
                // Don't want to be too specific here, as the number of sprites may change
                // in the future, but there must be something there.
                expect(legendSpriteCount).toBeGreaterThan(0);

                legendSpriteIds = {};

                for (var i = 0; i < legendSpriteCount; i++) {
                    legendSpriteIds[legendSprites[i].getId()] = true;
                }

                chart.setLegend({
                    type: 'sprite',
                    docked: 'top' // that's not a mistake, setting again to 'top'
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'top'
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendSurface = legend.getSurface(),
                    legendSprites = legendSurface.getItems(),
                    legendRect = legendSurface.getRect();

                // Sprites from the previous legend should not remain in the chart's
                // 'legend' surface. We should get the same number of sprites, not double
                // the sprites ...
                expect(legendSprites.length).toBe(legendSpriteCount);

                // ... and those sprites should be all different.
                for (var i = 0; i < legendSpriteCount; i++) {
                    expect(legendSprites[i].getId() in legendSpriteIds).toBe(false);
                }

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(legendSize.height);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side - legendSize.height);

                expect(legendRect[0]).toBe(0);
                expect(legendRect[1]).toBe(0);
                expect(legendRect[2]).toBe(side);
                expect(legendRect[3]).toBe(legendSize.height);

                chart.setLegend({
                    type: 'sprite',
                    docked: 'right'
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'right'
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendRect = legend.getSurface().getRect();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side - legendSize.width);
                expect(chartRect[3]).toBe(side);

                expect(legendRect[0]).toBe(side - legendSize.width);
                expect(legendRect[1]).toBe(0);
                expect(legendRect[2]).toBe(legendSize.width);
                expect(legendRect[3]).toBe(side);

                chart.setLegend({
                    type: 'sprite',
                    docked: 'bottom'
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'bottom'
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendRect = legend.getSurface().getRect();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side - legendSize.height);

                expect(legendRect[0]).toBe(0);
                expect(legendRect[1]).toBe(side - legendSize.height);
                expect(legendRect[2]).toBe(side);
                expect(legendRect[3]).toBe(legendSize.height);

                chart.setLegend({
                    type: 'sprite',
                    docked: 'left'
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'left'
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendRect = legend.getSurface().getRect();

                expect(chartRect[0]).toBe(legendSize.width);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side - legendSize.width);
                expect(chartRect[3]).toBe(side);

                expect(legendRect[0]).toBe(0);
                expect(legendRect[1]).toBe(0);
                expect(legendRect[2]).toBe(legendSize.width);
                expect(legendRect[3]).toBe(side);

                chart.setLegend(null);
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // legend: null
                var chartRect = chart.getChartRect();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side);

                chart.setLegend({
                    type: 'sprite',
                    docked: 'right' // creating ...
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                var legend = chart.getLegend(),
                    legendSurface = legend.getSurface();

                expect(legendSurface.getHidden()).toBe(false);

                chart.getLegend().setHidden(true); // ... and hiding
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'right',
                // hidden: true
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSurface = legend.getSurface();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side);

                expect(legendSurface.getHidden()).toBe(true);

                chart.getLegend().setHidden(false);
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'right',
                // hidden: false
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSize = legend.getSize(),
                    legendSurface = legend.getSurface();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side - legendSize.width);
                expect(chartRect[3]).toBe(side);

                expect(legendSurface.getHidden()).toBe(false);

                chart.setLegend({
                    type: 'sprite',
                    docked: 'right',
                    hidden: true // creating already hidden
                });
                layoutDone = false;
            });

            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                // docked: 'right',
                // hidden: true
                var chartRect = chart.getChartRect(),
                    legend = chart.getLegend(),
                    legendSurface = legend.getSurface();

                expect(chartRect[0]).toBe(0);
                expect(chartRect[1]).toBe(0);
                expect(chartRect[2]).toBe(side);
                expect(chartRect[3]).toBe(side);

                expect(legendSurface.getHidden()).toBe(true);
            });

        });
    });

    describe("updateTheme", function() {
        var storeData = generateStoreData(2);

        var chartConfig = {
            animation: false,
            width: 400,
            height: 300,
            renderTo: document.body,
            axes: [{
                type: 'numeric',
                position: 'left',
                adjustByMajorUnit: true,
                grid: true,
                fields: ['data1'],
                minimum: 0
            }, {
                type: 'category',
                position: 'bottom',
                grid: true,
                fields: ['month'],
                label: {
                    rotate: {
                        degrees: -45
                    }
                }
            }],
            series: [{
                type: 'bar',
                title: [ 'IE', 'Firefox' ],
                xField: 'month',
                yField: [ 'data1', 'data2' ],
                stacked: true,
                style: {
                    opacity: 0.80
                },
                highlight: {
                    fillStyle: 'yellow'
                }
            }]
        };

        var store, chart;

        beforeEach(function() {
            store = new Ext.data.Store({
                fields: [ 'month', 'data1', 'data2' ],
                data: storeData
            });
        });

        afterEach(function() {
            Ext.destroy(chart, store);
        });

        it("should use the style from the theme, " +
            "if the user hasn't provided their own config", function() {
            var CustomTheme = Ext.define(null, {
                extend: 'Ext.chart.theme.Base',
                singleton: true,
                config: {
                    legend: {
                        label: {
                            fontSize: 15,
                            fontWeight: 'bold',
                            fontFamily: 'Tahoma',
                            fillStyle: '#ff0000'
                        },
                        border: {
                            lineWidth: 2,
                            radius: 5,
                            fillStyle: '#ffff00',
                            strokeStyle: '#ff0000'
                        }
                    }
                }
            });

            var config = Ext.merge({
                theme: new CustomTheme,
                store: store,
                legend: {
                    type: 'sprite',
                    docked: 'top'
                }
            }, chartConfig);

            chart = new Ext.chart.CartesianChart(config);

            var legend = chart.getLegend();

            var borderSprite = legend.getBorder();

            var itemSprites = legend.getSprites();

            expect(borderSprite.attr.lineWidth).toBe(2);
            expect(borderSprite.attr.radius).toBe(5);
            expect(borderSprite.attr.fillStyle).toBe('#ffff00');
            expect(borderSprite.attr.strokeStyle).toBe('#ff0000');

            for (var i = 0, ln = itemSprites.length; i < ln; i++) {
                var label = itemSprites[i].getLabel();

                expect(label.attr.fontSize).toBe('15px');
                expect(label.attr.fontWeight).toBe('bold');
                expect(label.attr.fontFamily).toBe('Tahoma');
                expect(label.attr.fillStyle).toBe('#ff0000');
            }
        });

        it("should should use the style from the user config, if it was provided", function() {
            var config = Ext.merge({
                store: store,
                legend: {
                    type: 'sprite',
                    docked: 'top',
                    label: {
                        fontSize: 15,
                        fontWeight: 'bold',
                        fontFamily: 'Tahoma',
                        fillStyle: '#ff0000'
                    },
                    border: {
                        lineWidth: 2,
                        radius: 5,
                        fillStyle: '#ffff00',
                        strokeStyle: '#ff0000'
                    }
                }
            }, chartConfig);

            chart = new Ext.chart.CartesianChart(config);

            var legend = chart.getLegend();

            var borderSprite = legend.getBorder();

            var itemSprites = legend.getSprites();

            expect(borderSprite.attr.lineWidth).toBe(2);
            expect(borderSprite.attr.radius).toBe(5);
            expect(borderSprite.attr.fillStyle).toBe('#ffff00');
            expect(borderSprite.attr.strokeStyle).toBe('#ff0000');

            for (var i = 0, ln = itemSprites.length; i < ln; i++) {
                var label = itemSprites[i].getLabel();

                expect(label.attr.fontSize).toBe('15px');
                expect(label.attr.fontWeight).toBe('bold');
                expect(label.attr.fontFamily).toBe('Tahoma');
                expect(label.attr.fillStyle).toBe('#ff0000');
            }
        });
    });

    describe("store", function() {
        var storeData = generateStoreData(4),
            store, chart, legend;

        beforeEach(function() {
            var layoutEndSpy;

            store = new Ext.data.Store({
                fields: [ 'month', 'data1', 'data2', 'data3', 'data4' ],
                data: storeData
            });

            chart = new Ext.chart.CartesianChart({
                animation: false,
                width: 400,
                height: 300,
                renderTo: document.body,

                store: store,
                legend: {
                    type: 'sprite',
                    docked: 'top'
                },
                axes: [{
                    type: 'numeric',
                    position: 'left',
                    adjustByMajorUnit: true,
                    grid: true,
                    fields: ['data1'],
                    minimum: 0
                }, {
                    type: 'category',
                    position: 'bottom',
                    grid: true,
                    fields: ['month'],
                    label: {
                        rotate: {
                            degrees: -45
                        }
                    }
                }],
                series: [{
                    type: 'bar',
                    title: [ 'IE', 'Firefox', 'Chrome', 'Safari' ],
                    xField: 'month',
                    yField: [ 'data1', 'data2', 'data3', 'data4' ],
                    stacked: true,
                    style: {
                        opacity: 0.80
                    },
                    highlight: {
                        fillStyle: 'yellow'
                    }
                }]
            });
            legend = chart.getLegend();
            layoutEndSpy = spyOn(chart, 'onLayoutEnd').andCallThrough();

            waitsForSpy(layoutEndSpy, "chart layout to finish");
        });

        afterEach(function() {
            Ext.destroy(chart, store);
        });

        it("should trigger sprite/layout update on data update", function() {
            var series = chart.getSeries()[0],
                oldBorderWidth, newBorderWidth,
                oldSecondItem, oldSecondItemX, newSecondItem, newSecondItemX;

            runs(function() {
                oldBorderWidth = legend.borderSprite.getBBox().width;
                oldSecondItem = legend.getSprites()[1];
                oldSecondItemX = oldSecondItem.getBBox().x;
                expect(oldSecondItemX > 0).toBe(true);
                series.setTitle([ 'Edge', 'Firewall', 'Cross', 'Savanna' ]);
            });

            // Wait for the required test conditions to become true
            waitsFor(function() {
                newBorderWidth = legend.borderSprite.getBBox().width;
                newSecondItem = legend.getSprites()[1];
                newSecondItemX = newSecondItem.getBBox().x;

                return newSecondItem === oldSecondItem &&
                        newSecondItem.getLabel().attr.text === 'Firewall' &&
                        newSecondItemX > oldSecondItemX;
            });
        });

        it("should trigger sprite/layout update on data change", function() {
            var series = chart.getSeries()[0],
                oldBorderWidth, newBorderWidth,
                oldSecondItem, oldSecondItemX, newSecondItem, newSecondItemX;

            runs(function() {
                oldBorderWidth = legend.borderSprite.getBBox().width;
                oldSecondItem = legend.getSprites()[1];
                oldSecondItemX = oldSecondItem.getBBox().x;
                expect(oldSecondItemX > 0).toBe(true);
                series.setTitle([ 'IE', 'Chrome', 'Safari' ]);
            });

            // Wait for the required test conditions to become true
            waitsFor(function() {
                newBorderWidth = legend.borderSprite.getBBox().width;
                newSecondItem = legend.getSprites()[1];
                newSecondItemX = newSecondItem.getBBox().x;

                // newSecondItem === oldSecondItem since the sprite should be reused. 
                // newSecondItemX === oldSecondItemX since the second sprite now displays the third 
                // title ('Chrome'),but because the whole legend is centered, and has 4 items only, the
                // position of 'Firefox' earlier should match position of 'Chrome' now.

                return newSecondItem === oldSecondItem &&
                    newSecondItem.getLabel().attr.text === 'Chrome' &&
                    legend.getSprites().length === 4 &&
                    newSecondItemX === oldSecondItemX &&
                    legend.getSprites()[3].getLabel().attr.text === 'data4';
            });
        });

        it("should trigger sprite/layout update on data sort", function() {
            var oldBorderWidth, newBorderWidth,
                performLayoutSpy = spyOn(legend, 'performLayout').andCallThrough(),
                sprites = legend.getSprites();

            function checkPositions(sprites) {
                expect(sprites[0].getBBox().x < sprites[1].getBBox().x).toBe(true);
                expect(sprites[1].getBBox().x < sprites[2].getBBox().x).toBe(true);
                expect(sprites[2].getBBox().x < sprites[3].getBBox().x).toBe(true);
            }

            runs(function() {
                // Initial positions:
                // IE - Firefox - Chrome - Safari
                checkPositions(sprites);

                oldBorderWidth = legend.borderSprite.getBBox().width;
                chart.legendStore.sort('name', 'DESC');
                performLayoutSpy.reset();
            });

           waitsForSpy(performLayoutSpy, "legend layout to finish after DESC sort");

            runs(function() {
                newBorderWidth = legend.borderSprite.getBBox().width;

                // Relative positions of the sprites should stay the same.
                checkPositions(sprites);

                // The sum of all sprite widths should stay the same,
                // and thus the legend border width too.
                // Safari - IE - Firefox - Chrome
                expect(sprites[0].getLabel().attr.text).toBe('Safari');
                expect(sprites[1].getLabel().attr.text).toBe('IE');
                expect(sprites[2].getLabel().attr.text).toBe('Firefox');
                expect(sprites[3].getLabel().attr.text).toBe('Chrome');

                chart.legendStore.sort('name', 'ASC');
                performLayoutSpy.reset();
            });

           waitsForSpy(performLayoutSpy, "legend layout to finish after ASC sort");

            runs(function() {
                // Relative positions of the sprites should stay the same.
                checkPositions(sprites);

                // Chrome - Firefox - IE - Safari
                expect(sprites[0].getLabel().attr.text).toBe('Chrome');
                expect(sprites[1].getLabel().attr.text).toBe('Firefox');
                expect(sprites[2].getLabel().attr.text).toBe('IE');
                expect(sprites[3].getLabel().attr.text).toBe('Safari');
            });
        });
    });

    describe('series colors', function() {
        var chart, layoutEnd;

        var colors1 = ['red', 'blue', 'green', 'orange', 'yellow'];

        var colors2 = ['gold', 'cyan', 'magenta', 'lime', 'navy'];

        var n = colors1.length;

        var data = (function() {
            var data = [];

            for (var i = 0; i < n; i++) {
                var point = {
                    x: 'cat' + (i + 1)
                };

                for (var j = 0; j < n; j++) {
                    point['y' + (j + 1)] = j + 1;
                }

                data.push(point);
            }

            return data;
        })();

        afterEach(function() {
            chart = Ext.destroy(chart);
            layoutEnd = false;
        });

        it('should use theme colors in a cartesian (bar) chart', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'bar',
                        xField: 'x',
                        yField: ['y1', 'y2', 'y3', 'y4', 'y5']
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites(),
                    themeColors = chart.getTheme().getColors();

                for (var i = 0; i < n; i++) {
                    expect(seriesSprites[i].attr.fillStyle).toBe(themeColors[i]);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(themeColors[i]);
                }
            });
        });
        it('should use theme colors in a polar (pie3d) chart', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'pie3d',
                        angleField: 'y1',
                        label: {
                            field: 'x'
                        }
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites(),
                    themeColors = chart.getTheme().getColors();

                for (var i = 0; i < n; i++) {
                    expect(seriesSprites[i * series.spritesPerSlice].attr.baseColor).toBe(themeColors[i]);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(themeColors[i]);
                }
            });
        });
        it('should use colors from the series "colors" config (cartesian, bar)', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'bar',
                        xField: 'x',
                        yField: ['y1', 'y2', 'y3', 'y4', 'y5'],
                        colors: colors1.slice()
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites();

                for (var i = 0; i < n; i++) {
                    var hexColor = Ext.util.Color.fly(colors1[i]).toString();

                    expect(seriesSprites[i].attr.fillStyle).toBe(hexColor);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(hexColor);
                }
            });
        });
        it('should use colors from the series "colors" config (polar, pie3d)', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'pie3d',
                        angleField: 'y1',
                        label: {
                            field: 'x'
                        },
                        colors: colors1.slice()
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites();

                for (var i = 0; i < n; i++) {
                    var hexColor = Ext.util.Color.fly(colors1[i]).toString();

                    expect(seriesSprites[i * series.spritesPerSlice].attr.baseColor).toBe(hexColor);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(hexColor);
                }
            });
        });
        it('should reflect dynamic changes to the series "colors" config (cartesian, bar)', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'cartesian',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'bar',
                        xField: 'x',
                        yField: ['y1', 'y2', 'y3', 'y4', 'y5'],
                        colors: colors1.slice()
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                layoutEnd = false;
                chart.getSeries()[0].setColors(colors2.slice());
            });
            waits(1);
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites();

                for (var i = 0; i < n; i++) {
                    var hexColor = Ext.util.Color.fly(colors2[i]).toString();

                    expect(seriesSprites[i].attr.fillStyle).toBe(hexColor);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(hexColor);
                }
            });
        });
        it('should reflect dynamic changes to the series "colors" config (polar, pie3d)', function() {
            runs(function() {
                chart = Ext.create({
                    xtype: 'polar',
                    animation: false,
                    renderTo: document.body,
                    width: 400,
                    height: 400,
                    store: {
                        data: data.slice()
                    },
                    legend: {
                        type: 'sprite',
                        docked: 'right'
                    },
                    series: [{
                        type: 'pie3d',
                        angleField: 'y1',
                        label: {
                            field: 'x'
                        },
                        colors: colors1.slice()
                    }],
                    listeners: {
                        layout: function() {
                            layoutEnd =  true;
                        }
                    }
                });
            });
            waitsFor(function() {
                return layoutEnd;
            });
            runs(function() {
                layoutEnd = false;
                chart.getSeries()[0].setColors(colors2.slice());
            });
            waits(1);
            runs(function() {
                var series = chart.getSeries()[0],
                    seriesSprites = series.getSprites(),
                    legendSprites = chart.getLegend().getSprites();

                for (var i = 0; i < n; i++) {
                    var hexColor = Ext.util.Color.fly(colors2[i]).toString();

                    expect(seriesSprites[i * series.spritesPerSlice].attr.baseColor).toBe(hexColor);
                    expect(legendSprites[i].getMarker().attr.fillStyle).toBe(hexColor);
                }
            });
        });
    });

    describe('long legends', function() {
        var store, chart, layoutDone;

        beforeEach(function() {
            chart = null;
            store = Ext.create('Ext.data.JsonStore', {
                fields: ['os', 'data1' ],
                data: [
                    { os: 'BlackBerry', data1: 20.4 },
                    { os: 'iOS', data1: 29.6 },
                    { os: 'Windows Phone', data1: 24.5 },
                    { os: 'Others, very long to break the example', data1: 25.5 }
                ]
            });

            chart = Ext.create({
                xtype: 'polar',
                width: 200,
                height: 500,
                renderTo: document.body,
                store: store,
                insetPadding: 50,
                innerPadding: 20,
                legend: {
                    docked: 'bottom',
                    position: 'right'
                },
                interactions: ['rotate', 'itemhighlight'],
                listeners: {
                    layout: function() {
                        layoutDone = true;
                    }
                },
                series: [{
                    type: 'pie',
                    angleField: 'data1',
                    label: { field: 'os' },
                    highlight: true,
                    tooltip: {
                        trackMouse: true,

                        renderer: function(tt, storeItem, item) {
                            tt.setHtml(storeItem.get('os') + ': ' + storeItem.get('data1') + '%');
                        }
                    }
                }]
            });
        });

        afterEach(function() {
            Ext.destroy(chart, store);
        });

        it("should not enter infinite loop if provided surfaceWidth is small", function() {
            waitsFor(function() {
                return layoutDone;
            });

            runs(function() {
                var canvas = document.querySelector('.x-surface-canvas');

                expect(canvas).not.toBe(null);
            });

        });

    });
});
