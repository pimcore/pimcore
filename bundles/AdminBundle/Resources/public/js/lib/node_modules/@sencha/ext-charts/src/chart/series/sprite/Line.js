/**
 * @class Ext.chart.series.sprite.Line
 * @extends Ext.chart.series.sprite.Aggregative
 *
 * Line series sprite.
 */
Ext.define('Ext.chart.series.sprite.Line', {
    alias: 'sprite.lineSeries',
    extend: 'Ext.chart.series.sprite.Aggregative',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Object} [curve={type: 'linear'}]
                 * The type of curve that connects the data points.
                 *
                 * For example:
                 *
                 *     // The data points are connected by line segments.
                 *     // This is the default setting.
                 *     curve: {
                 *         type: 'linear'
                 *     }
                 *
                 *     // Cardinal spline interpolation is used to produce the curve
                 *     // that connects the data points. The `tension` parameter can
                 *     // be used to control the smoothness of the curve. A tension
                 *     // of 0 corresponds to infinite tension, which results in straight
                 *     // lines between data points. A tension of 1 corresponds to
                 *     // no tension, allowing the spline to take the path of least
                 *     // total bend. With tension values greater than 1, the curve
                 *     // behaves like a compressed spring, pushed to take a longer path.
                 *     // A cardinal spline with a tension of 0.5 is a special case.
                 *     // It is then called a Catmull-Rom spline. Catmull-Rom splines are
                 *     // thought to be esthetically pleasing and are quite common.
                 *     // Note: spline interpolation only works on gapless data.
                 *     curve: {
                 *         type: 'cardinal,
                 *         tension: 0.5
                 *     }
                 *
                 *     // Produces a natural cubic spline with the second derivative
                 *     // of the spline set to zero at the endpoints.
                 *     curve: {
                 *         type: 'natural'
                 *     }
                 *
                 *     // The data points are connected by alternating horizontal and
                 *     // vertical lines. The y-value changes after the x-value.
                 *     curve: {
                 *         type: 'step-after'
                 *     }
                 *
                 */
                curve: 'default',

                /**
                 * @cfg {Boolean} [fillArea=false]
                 * `true` if the sprite paints the area underneath the line.
                 */
                fillArea: 'bool',

                /**
                 * @cfg {"gap"/"connect"/"origin"} [nullStyle="gap"]
                 * Possible values:
                 * 'gap' - null points are rendered as gaps.
                 * 'connect' - non-null points are connected across null points, so that
                 * there is no gap, unless null points are at the beginning/end of the line.
                 * Only the visible data points are connected - if a visible data point
                 * is followed by a series of null points that go off screen and eventually
                 * terminate with a non-null point, the connection won't be made.
                 * 'origin' - null data points are rendered at the origin,
                 * which is the y-coordinate of a point where the x and y axes meet.
                 * This requires that at least the x-coordinate of a point is a valid value.
                 */
                nullStyle: 'enums(gap,connect,origin)',

                /**
                 * @cfg {Boolean} [preciseStroke=true]
                 * `true` if the line uses precise stroke.
                 */
                preciseStroke: 'bool',

                /**
                 * @private
                 * The x-axis associated with the Line series.
                 * We need to know the position of the x-axis to fill the area underneath
                 * the stroke properly.
                 */
                xAxis: 'default',

                /**
                 * @cfg {Number} [yCap=Math.pow(2, 20)]
                 * Absolute maximum y-value.
                 * Larger values will be capped to avoid rendering issues.
                 */
                // The 'default' processor is used here as we don't want this attribute to animate.
                yCap: 'default'
            },

            defaults: {
                curve: {
                    type: 'linear'
                },
                nullStyle: 'connect',
                fillArea: false,
                preciseStroke: true,
                xAxis: null,
                yCap: Math.pow(2, 20),
                yJump: 50
            },

            triggers: {
                dataX: 'dataX,bbox,curve',
                dataY: 'dataY,bbox,curve',
                curve: 'curve'
            },

            updaters: {
                curve: 'curveUpdater'
            }
        }
    },

    list: null,

    curveUpdater: function(attr) {
        var me = this,
            dataX = attr.dataX,
            dataY = attr.dataY,
            curve = attr.curve,
            smoothable = dataX && dataY && dataX.length > 2 && dataY.length > 2,
            type = curve.type;

        if (smoothable) {
            if (type === 'natural') {
                me.smoothX = Ext.draw.Draw.naturalSpline(dataX);
                me.smoothY = Ext.draw.Draw.naturalSpline(dataY);
            }
            else if (type === 'cardinal') {
                me.smoothX = Ext.draw.Draw.cardinalSpline(dataX, curve.tension);
                me.smoothY = Ext.draw.Draw.cardinalSpline(dataY, curve.tension);
            }
            else {
                smoothable = false;
            }
        }

        if (!smoothable) {
            delete me.smoothX;
            delete me.smoothY;
        }
    },

    updatePlainBBox: function(plain) {
        var attr = this.attr,
            ymin = Math.min(0, attr.dataMinY),
            ymax = Math.max(0, attr.dataMaxY);

        plain.x = attr.dataMinX;
        plain.y = ymin;
        plain.width = attr.dataMaxX - attr.dataMinX;
        plain.height = ymax - ymin;
    },

    drawStrip: function(ctx, strip) {
        var i, ln;

        ctx.moveTo(strip[0], strip[1]);

        for (i = 2, ln = strip.length; i < ln; i += 2) {
            ctx.lineTo(strip[i], strip[i + 1]);
        }
    },

    drawStraightStroke: function(surface, ctx, start, end, list, xAxis) {
        var me = this,
            attr = me.attr,
            nullStyle = attr.nullStyle,
            isConnect = nullStyle === 'connect',
            isOrigin = nullStyle === 'origin',
            renderer = attr.renderer,
            curve = attr.curve,
            step = curve.type === 'step-after',
            needMoveTo = true,
            ln = list.length,
            lineConfig = {
                type: 'line',
                smooth: false,
                step: step
            },

            rendererChanges, params, stripStartX,
            isValidX0, isValidX, isValidX1,
            isValidPoint0, isValidPoint, isValidPoint1,
            isGap, lastValidPoint, px, py,
            x, y, x0, y0, x1, y1, i,

            // 'strip' stores last continuous segment of the stroke,
            // which we may need to re-build, if there's a fill as well.
            // For example, if the renderer returned a style that needs
            // to be applied to the current step, or we reached a null
            // point in the data, where we have to fill the current continuous
            // segment, we build and close a path that will be filled, then
            // re-build the stroke path, using coordinates saved in the 'strip',
            // and render the stroke on top of the fill.
            strip = [];

        ctx.beginPath();

        for (i = 3; i < ln; i += 3) {
            x0 = list[i - 3];
            y0 = list[i - 2];
            x = list[i];
            y = list[i + 1];
            x1 = list[i + 3];
            y1 = list[i + 4];

            isValidX0 = Ext.isNumber(x0);
            isValidX = Ext.isNumber(x);
            isValidX1 = Ext.isNumber(x1);

            isValidPoint0 = isValidX0 && Ext.isNumber(y0);
            isValidPoint = isValidX && Ext.isNumber(y);
            isValidPoint1 = isValidX1 && Ext.isNumber(y1);

            if (isOrigin) {
                // If only the y-component isn't a valid number,
                // we can 'fix' it by setting it to value of y-origin.
                if (!isValidPoint0 && isValidX0) {
                    y0 = xAxis;
                    isValidPoint0 = true;
                }

                if (!isValidPoint && isValidX) {
                    y = xAxis;
                    isValidPoint = true;
                }

                if (!isValidPoint1 && isValidX1) {
                    y1 = xAxis;
                    isValidPoint1 = true;
                }
            }

            if (renderer) {
                lineConfig.x = x;
                lineConfig.y = y;
                lineConfig.x0 = x0;
                lineConfig.y0 = y0;
                params = [me, lineConfig, me.rendererData, start + i / 3];
                // callback(fn, scope, args, delay, caller)
                rendererChanges = Ext.callback(renderer, null, params, 0, me.getSeries());
            }

            if (isGap && isConnect && isValidPoint0 && lastValidPoint) {
                px = lastValidPoint[0];
                py = lastValidPoint[1];

                if (needMoveTo) {
                    ctx.beginPath();
                    ctx.moveTo(px, py);
                    strip.push(px, py);
                    stripStartX = px;
                    needMoveTo = false;
                }

                if (step) {
                    ctx.lineTo(x0, py);
                    strip.push(x0, py);
                }

                ctx.lineTo(x0, y0);
                strip.push(x0, y0);

                lastValidPoint = [x0, y0];
                isGap = false;
            }

            // Special case where we have an uninterrupted segment, followed
            // by a gap, then a valid point, then another gap. The uninterrupted
            // segment should be connenected with the dot situated between the gaps.
            if (isConnect && lastValidPoint && isValidPoint && !isValidPoint0) {
                x0 = lastValidPoint[0];
                y0 = lastValidPoint[1];
                isValidPoint0 = true;
            }

            // Remember last valid point to connect the gap
            // when the next valid point is encountered.
            if (isValidPoint) {
                lastValidPoint = [x, y];
            }

            if (isValidPoint0 && isValidPoint) {
                if (needMoveTo) {
                    ctx.beginPath();
                    ctx.moveTo(x0, y0);
                    strip.push(x0, y0);
                    stripStartX = x0;
                    needMoveTo = false;
                }
            }
            else {
                isGap = true;
                continue;
            }

            if (step) {
                ctx.lineTo(x, y0);
                strip.push(x, y0);
            }

            ctx.lineTo(x, y);
            strip.push(x, y);

            // If the next point is a gap, then we need to fill what
            // has been already rendered so far. The same applies
            // if the renderer returned some changes to apply to
            // the current step.
            if (rendererChanges || !isValidPoint1) {
                ctx.save();
                Ext.apply(ctx, rendererChanges);
                rendererChanges = null;

                if (attr.fillArea) {
                    ctx.lineTo(x, xAxis);
                    ctx.lineTo(stripStartX, xAxis);
                    ctx.closePath();
                    ctx.fill();
                }

                // Draw the line on top of the filled area.
                ctx.beginPath();
                me.drawStrip(ctx, strip);
                strip = [];
                ctx.stroke();
                ctx.restore();

                ctx.beginPath();
                // Take note that the starting point of a path has been reset
                // (as a result of filling a sub-path) and needs to be set again
                // for the line to continue in a proper manner.
                needMoveTo = true;
            }
        }
    },

    calculateScale: function(count, end) {
        var power = 0,
            n = count;

        while (n < end && count > 0) {
            power++;
            n += count >> power;
        }

        return Math.pow(2, power > 0 ? power - 1 : power);
    },

    drawSmoothStroke: function(surface, ctx, start, end, list, xAxis) {
        var me = this,
            attr = me.attr,
            step = attr.step,
            matrix = attr.matrix,
            renderer = attr.renderer,
            xx = matrix.getXX(),
            yy = matrix.getYY(),
            dx = matrix.getDX(),
            dy = matrix.getDY(),
            smoothX = me.smoothX,
            smoothY = me.smoothY,
            scale = me.calculateScale(attr.dataX.length, end),
            cx1, cy1, cx2, cy2, x, y, x0, y0,
            i, j, changes, params,
            lineConfig = {
                type: 'line',
                smooth: true,
                step: step
            };

        ctx.beginPath();
        ctx.moveTo(smoothX[start * 3] * xx + dx, smoothY[start * 3] * yy + dy);

        for (i = 0, j = start * 3 + 1; i < list.length - 3; i += 3, j += 3 * scale) {
            cx1 = smoothX[j] * xx + dx;
            cy1 = smoothY[j] * yy + dy;
            cx2 = smoothX[j + 1] * xx + dx;
            cy2 = smoothY[j + 1] * yy + dy;
            x = surface.roundPixel(list[i + 3]);
            y = list[i + 4];
            x0 = surface.roundPixel(list[i]);
            y0 = list[i + 1];

            if (renderer) {
                lineConfig.x0 = x0;
                lineConfig.y0 = y0;
                lineConfig.cx1 = cx1;
                lineConfig.cy1 = cy1;
                lineConfig.cx2 = cx2;
                lineConfig.cy2 = cy2;
                lineConfig.x = x;
                lineConfig.y = y;
                params = [me, lineConfig, me.rendererData, start + i / 3 + 1];
                changes = Ext.callback(renderer, null, params, 0, me.getSeries());
                ctx.save();
                Ext.apply(ctx, changes);
            }

            if (attr.fillArea) {
                ctx.moveTo(x0, y0);
                ctx.bezierCurveTo(cx1, cy1, cx2, cy2, x, y);
                ctx.lineTo(x, xAxis);
                ctx.lineTo(x0, xAxis);
                ctx.lineTo(x0, y0);
                ctx.closePath();
                ctx.fill();
                ctx.beginPath();
            }

            // Draw the line on top of the filled area.
            ctx.moveTo(x0, y0);
            ctx.bezierCurveTo(cx1, cy1, cx2, cy2, x, y);
            ctx.stroke();
            ctx.moveTo(x0, y0);
            ctx.closePath();

            if (renderer) {
                ctx.restore();
            }

            ctx.beginPath();
            ctx.moveTo(x, y);
        }

        // Prevent the last visible segment from being stroked twice
        // (second time by the ctx.fillStroke inside Path sprite 'render' method)
        ctx.beginPath();
    },

    drawLabel: function(text, dataX, dataY, labelId, rect) {
        var me = this,
            attr = me.attr,
            label = me.getMarker('labels'),
            labelTpl = label.getTemplate(),
            labelCfg = me.labelCfg || (me.labelCfg = {}),
            surfaceMatrix = me.surfaceMatrix,
            labelX, labelY,
            labelOverflowPadding = attr.labelOverflowPadding,
            halfHeight, labelBBox,
            changes, params, hasPendingChanges;

        // The coordinates below (data point converted to surface coordinates)
        // are just for the renderer to give it a notion of where the label will be positioned.
        // The actual position of the label will be different
        // (unless the renderer returns x/y coordinates in the changes object)
        // and depend on several things including the size of the text,
        // which has to be measured after the renderer call,
        // since text can be modified by the renderer.
        labelCfg.x = surfaceMatrix.x(dataX, dataY);
        labelCfg.y = surfaceMatrix.y(dataX, dataY);

        if (attr.flipXY) {
            labelCfg.rotationRads = Math.PI * 0.5;
        }
        else {
            labelCfg.rotationRads = 0;
        }

        labelCfg.text = text;

        if (labelTpl.attr.renderer) {
            params = [text, label, labelCfg, me.rendererData, labelId];
            changes = Ext.callback(labelTpl.attr.renderer, null, params, 0, me.getSeries());

            if (typeof changes === 'string') {
                labelCfg.text = changes;
            }
            else if (typeof changes === 'object') {
                if ('text' in changes) {
                    labelCfg.text = changes.text;
                }

                hasPendingChanges = true;
            }
        }

        labelBBox = me.getMarkerBBox('labels', labelId, true);

        if (!labelBBox) {
            me.putMarker('labels', labelCfg, labelId);
            labelBBox = me.getMarkerBBox('labels', labelId, true);
        }

        halfHeight = labelBBox.height / 2;
        labelX = dataX;

        switch (labelTpl.attr.display) {
            case 'under':
                labelY = dataY - halfHeight - labelOverflowPadding;
                break;

            case 'rotate':
                labelX += labelOverflowPadding;
                labelY = dataY - labelOverflowPadding;
                labelCfg.rotationRads = -Math.PI / 4;
                break;
            default: // 'over'
                labelY = dataY + halfHeight + labelOverflowPadding;
        }

        labelCfg.x = surfaceMatrix.x(labelX, labelY);
        labelCfg.y = surfaceMatrix.y(labelX, labelY);

        if (hasPendingChanges) {
            Ext.apply(labelCfg, changes);
        }

        me.putMarker('labels', labelCfg, labelId);
    },

    drawMarker: function(x, y, index) {
        var me = this,
            attr = me.attr,
            renderer = attr.renderer,
            surfaceMatrix = me.surfaceMatrix,
            markerCfg = {},
            changes, params;

        if (renderer && me.getMarker('markers')) {
            markerCfg.type = 'marker';
            markerCfg.x = x;
            markerCfg.y = y;
            params = [me, markerCfg, me.rendererData, index];
            changes = Ext.callback(renderer, null, params, 0, me.getSeries());

            if (changes) {
                Ext.apply(markerCfg, changes);
            }
        }

        markerCfg.translationX = surfaceMatrix.x(x, y);
        markerCfg.translationY = surfaceMatrix.y(x, y);

        delete markerCfg.x;
        delete markerCfg.y;

        me.putMarker('markers', markerCfg, index, !renderer);
    },

    drawStroke: function(surface, ctx, start, end, list, xAxis) {
        var me = this,
            isSmooth = me.smoothX && me.smoothY;

        if (isSmooth) {
            me.drawSmoothStroke(surface, ctx, start, end, list, xAxis);
        }
        else {
            me.drawStraightStroke(surface, ctx, start, end, list, xAxis);
        }
    },

    renderAggregates: function(aggregates, start, end, surface, ctx, clip, rect) {
        var me = this,
            attr = me.attr,
            dataX = attr.dataX,
            dataY = attr.dataY,
            labels = attr.labels,
            xAxis = attr.xAxis,
            yCap = attr.yCap,
            isSmooth = attr.smooth && me.smoothX && me.smoothY,
            isDrawLabels = labels && me.getMarker('labels'),
            isDrawMarkers = me.getMarker('markers'),
            matrix = attr.matrix,
            pixel = surface.devicePixelRatio,
            xx = matrix.getXX(),
            yy = matrix.getYY(),
            dx = matrix.getDX(),
            dy = matrix.getDY(),
            list = me.list || (me.list = []),
            minXs = aggregates.minX,
            maxXs = aggregates.maxX,
            minYs = aggregates.minY,
            maxYs = aggregates.maxY,
            idx = aggregates.startIdx,
            isContinuousLine = true,
            isValidMinX, isValidMaxX,
            isValidMinY, isValidMaxY,
            xAxisOrigin, isVerticalX,
            x, y, i, index, minX, maxX, minY, maxY,
            lastPointX, lastPointY, firstPointX, firstPointY;

        me.rendererData = { store: me.getStore() };
        list.length = 0;

        // Say we have 7 y-items (attr.dataY): [20, 19, 17, 15, 11, 10, 14]
        //         and 7 x-items (attr.dataX): [0,   1,  2,  3,  4,  5,  6].
        // Then aggregates.startIdx is an aggregated index,
        // where every other item is skipped on each aggregation level:
        // [0, 1, 2, 3, 4, 5, 6,
        //  0, 2, 4, 6,
        //  0, 4,
        //  0]
        // aggregates.minY
        // [20, 19, 17, 15, 11, 10, 14,
        //  19, 15, 10, 14,
        //  15, 10,
        //  10]
        // aggregates.maxY
        // [20, 19, 17, 15, 11, 10, 14,
        //  20, 17, 11, 14,
        //  20, 14,
        //  20]
        // aggregates.minX is
        // [0, 1, 2, 3, 4, 5, 6,
        //  1, 3, 5, 6, // TODO: why this order for min?
        //  3, 5,       // TODO: why this inconsistency?
        //  5]
        // aggregates.maxX is
        // [0, 1, 2, 3, 4, 5, 6,
        //  0, 2, 4, 6,
        //  0, 6,
        //  0]

        // Create a list of the form [x0, y0, idx0, x1, y1, idx1, ...],
        // where each x,y pair is a coordinate representing original data point
        // at the idx position.
        for (i = start; i < end; i++) {
            minX = minXs[i];
            maxX = maxXs[i];
            minY = minYs[i];
            maxY = maxYs[i];

            isValidMinX = Ext.isNumber(minX);
            isValidMinY = Ext.isNumber(minY);
            isValidMaxX = Ext.isNumber(maxX);
            isValidMaxY = Ext.isNumber(maxY);

            if (minX < maxX) {
                list.push(
                    isValidMinX ? (minX * xx + dx) : null,
                    isValidMinY ? (minY * yy + dy) : null,
                    idx[i]
                );
                list.push(
                    isValidMaxX ? (maxX * xx + dx) : null,
                    isValidMaxY ? (maxY * yy + dy) : null,
                    idx[i]
                );
            }
            else if (minX > maxX) {
                list.push(
                    isValidMaxX ? (maxX * xx + dx) : null,
                    isValidMaxY ? (maxY * yy + dy) : null,
                    idx[i]
                );
                list.push(
                    isValidMinX ? (minX * xx + dx) : null,
                    isValidMinY ? (minY * yy + dy) : null,
                    idx[i]
                );
            }
            else {
                list.push(
                    isValidMaxX ? (maxX * xx + dx) : null,
                    isValidMaxY ? (maxY * yy + dy) : null,
                    idx[i]
                );
            }
        }

        if (list.length) {
            for (i = 0; i < list.length; i += 3) {
                x = list[i];
                y = list[i + 1];

                if (Ext.isNumber(x) && Ext.isNumber(y)) {
                    if (y > yCap) {
                        y = yCap;
                    }
                    else if (y < -yCap) {
                        y = -yCap;
                    }

                    list[i + 1] = y;
                }
                else {
                    isContinuousLine = false;
                    continue;
                }

                index = list[i + 2];

                if (isDrawMarkers) {
                    me.drawMarker(x, y, index);
                }

                if (isDrawLabels && labels[index]) {
                    me.drawLabel(labels[index], x, y, index, rect);
                }
            }

            me.isContinuousLine = isContinuousLine;

            if (isSmooth && !isContinuousLine) {
                Ext.raise("Line smoothing in only supported for gapless data, " +
                    "where all data points are finite numbers.");
            }

            if (xAxis) {
                isVerticalX = xAxis.getAlignment() === 'vertical';

                if (Ext.isNumber(xAxis.floatingAtCoord)) {
                    xAxisOrigin = (isVerticalX ? rect[2] : rect[3]) - xAxis.floatingAtCoord;
                }
                else {
                    xAxisOrigin = isVerticalX ? rect[0] : rect[1];
                }
            }
            else {
                xAxisOrigin = attr.flipXY ? rect[0] : rect[1];
            }

            if (attr.preciseStroke) {
                if (attr.fillArea) {
                    ctx.fill();
                }

                if (attr.transformFillStroke) {
                    attr.inverseMatrix.toContext(ctx);
                }

                me.drawStroke(surface, ctx, start, end, list, xAxisOrigin);

                if (attr.transformFillStroke) {
                    attr.matrix.toContext(ctx);
                }

                ctx.stroke();
            }
            else {
                me.drawStroke(surface, ctx, start, end, list, xAxisOrigin);

                if (isContinuousLine && isSmooth && attr.fillArea && !attr.renderer) {
                    lastPointX = dataX[dataX.length - 1] * xx + dx + pixel;
                    lastPointY = dataY[dataY.length - 1] * yy + dy;
                    firstPointX = dataX[0] * xx + dx - pixel;
                    firstPointY = dataY[0] * yy + dy;

                    // Fill the area from the series to the xAxis in case there
                    // are no gaps and no renderer is used, in which case the
                    // area would be filled per uninterrupted segment or per
                    // step, instead of being filled a single pass.
                    ctx.lineTo(lastPointX, lastPointY);
                    ctx.lineTo(lastPointX, xAxisOrigin - attr.lineWidth);
                    ctx.lineTo(firstPointX, xAxisOrigin - attr.lineWidth);
                    ctx.lineTo(firstPointX, firstPointY);
                }

                if (attr.transformFillStroke) {
                    attr.matrix.toContext(ctx);
                }

                // Prevent the reverse transform to fix floating point error.
                if (attr.fillArea) {
                    ctx.fillStroke(attr, true);
                }
                else {
                    ctx.stroke(true);
                }
            }
        }
    }
});
