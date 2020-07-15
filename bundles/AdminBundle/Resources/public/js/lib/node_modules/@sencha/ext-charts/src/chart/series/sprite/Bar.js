/**
 * @class Ext.chart.series.sprite.Bar
 * @extends Ext.chart.series.sprite.StackedCartesian
 *
 * Draws a sprite used in the bar series.
 */
Ext.define('Ext.chart.series.sprite.Bar', {
    alias: 'sprite.barSeries',
    extend: 'Ext.chart.series.sprite.StackedCartesian',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Number} [minBarWidth=2] The minimum bar width.
                 */
                minBarWidth: 'number',

                /**
                 * @cfg {Number} [maxBarWidth=100] The maximum bar width.
                 */
                maxBarWidth: 'number',

                /**
                 * @cfg {Number} [minGapWidth=5] The minimum gap between bars.
                 */
                minGapWidth: 'number',

                /**
                 * @cfg {Number} [radius=0] The degree of rounding for rounded bars.
                 */
                radius: 'number',

                /**
                 * @cfg {Number} [inGroupGapWidth=3] The gap between grouped bars.
                 */
                inGroupGapWidth: 'number'
            },
            defaults: {
                minBarWidth: 2,
                maxBarWidth: 100,
                minGapWidth: 5,
                inGroupGapWidth: 3,
                radius: 0
            }
        }
    },

    drawLabel: function(text, dataX, dataStartY, dataY, labelId) {
        var me = this,
            attr = me.attr,
            label = me.getMarker('labels'),
            labelTpl = label.getTemplate(),
            labelCfg = me.labelCfg || (me.labelCfg = {}),
            surfaceMatrix = me.surfaceMatrix,
            labelOverflowPadding = attr.labelOverflowPadding,
            labelDisplay = labelTpl.attr.display,
            labelOrientation = labelTpl.attr.orientation,
            isVerticalText = (labelOrientation === 'horizontal' && attr.flipXY) ||
                             (labelOrientation === 'vertical' && !attr.flipXY) ||
                             !labelOrientation,
            calloutLine = labelTpl.getCalloutLine(),
            labelY, halfText, labelBBox, calloutLineLength,
            changes, hasPendingChanges, params;

        // The coordinates below (data point converted to surface coordinates)
        // are just for the renderer to give it a notion of where the label will be positioned.
        // The actual position of the label will be different
        // (unless the renderer returns x/y coordinates in the changes object)
        // and depend on several things including the size of the text,
        // which has to be measured after the renderer call,
        // since text can be modified by the renderer.
        labelCfg.x = surfaceMatrix.x(dataX, dataY);
        labelCfg.y = surfaceMatrix.y(dataX, dataY);

        if (calloutLine) {
            calloutLineLength = calloutLine.length;
        }
        else {
            calloutLineLength = 0;
        }

        // Set defaults
        if (!attr.flipXY) {
            labelCfg.rotationRads = -Math.PI * 0.5;
        }
        else {
            labelCfg.rotationRads = 0;
        }

        labelCfg.calloutVertical = !attr.flipXY;

        // Check if we have a specific orientation specified, if so, set
        // the appropriate values.
        switch (labelOrientation) {
            case 'horizontal':
                labelCfg.rotationRads = 0;
                labelCfg.calloutVertical = false;
                break;

            case 'vertical':
                labelCfg.rotationRads = -Math.PI * 0.5;
                labelCfg.calloutVertical = true;
                break;
        }

        labelCfg.text = text;

        if (labelTpl.attr.renderer) {
            // The label instance won't exist on first render before the renderer is called,
            // it's only created later by `me.putMarker` after the renderer call. To make
            // sure the renderer always can access the label instance, we make this check here.
            if (!label.get(labelId)) {
                label.putMarkerFor('labels', {}, labelId);
            }

            params = [text, label, labelCfg, { store: me.getStore() }, labelId];
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

        if (calloutLineLength > 0) {
            halfText = calloutLineLength;
        }
        else if (calloutLineLength === 0) {
            halfText = (isVerticalText ? labelBBox.width : labelBBox.height) / 2;
        }
        else {
            halfText =
                (isVerticalText ? labelBBox.width : labelBBox.height) / 2 + labelOverflowPadding;
        }

        if (dataStartY > dataY) {
            halfText = -halfText;
        }

        if (isVerticalText) {
            labelY = (labelDisplay === 'insideStart')
                ? dataStartY + halfText
                : dataY - halfText;
        }
        else {
            labelY = (labelDisplay === 'insideStart')
                ? dataStartY + labelOverflowPadding * 2
                : dataY - labelOverflowPadding * 2;
        }

        labelCfg.x = surfaceMatrix.x(dataX, labelY);
        labelCfg.y = surfaceMatrix.y(dataX, labelY);

        labelY = (labelDisplay === 'insideStart') ? dataStartY : dataY;
        labelCfg.calloutStartX = surfaceMatrix.x(dataX, labelY);
        labelCfg.calloutStartY = surfaceMatrix.y(dataX, labelY);

        labelY = (labelDisplay === 'insideStart') ? dataStartY - halfText : dataY + halfText;
        labelCfg.calloutPlaceX = surfaceMatrix.x(dataX, labelY);
        labelCfg.calloutPlaceY = surfaceMatrix.y(dataX, labelY);

        labelCfg.calloutColor = (calloutLine && calloutLine.color) || me.attr.fillStyle;

        if (calloutLine) {
            if (calloutLine.width) {
                labelCfg.calloutWidth = calloutLine.width;
            }
        }
        else {
            labelCfg.calloutColor = 'none';
        }

        if (dataStartY > dataY) {
            halfText = -halfText;
        }

        if (Math.abs(dataY - dataStartY) <= halfText * 2 || labelDisplay === 'outside') {
            labelCfg.callout = 1;
        }
        else {
            labelCfg.callout = 0;
        }

        if (hasPendingChanges) {
            Ext.apply(labelCfg, changes);
        }

        me.putMarker('labels', labelCfg, labelId);
    },

    drawBar: function(ctx, surface, rect, left, top, right, bottom, index) {
        var me = this,
            itemCfg = {},
            renderer = me.attr.renderer,
            changes;

        itemCfg.x = left;
        itemCfg.y = top;
        itemCfg.width = right - left;
        itemCfg.height = bottom - top;
        itemCfg.radius = me.attr.radius;

        if (renderer) {
            changes = Ext.callback(renderer, null, [me, itemCfg, { store: me.getStore() }, index],
                                   0, me.getSeries());
            Ext.apply(itemCfg, changes);
        }

        me.putMarker('items', itemCfg, index, !renderer);
    },

    renderClipped: function(surface, ctx, dataClipRect) {
        if (this.cleanRedraw) {
            return;
        }

        // eslint-disable-next-line vars-on-top
        var me = this,
            attr = me.attr,
            dataX = attr.dataX,
            dataY = attr.dataY,
            dataText = attr.labels,
            dataStartY = attr.dataStartY,
            groupCount = attr.groupCount,
            groupOffset = attr.groupOffset - (groupCount - 1) * 0.5,
            inGroupGapWidth = attr.inGroupGapWidth,
            lineWidth = ctx.lineWidth,
            matrix = attr.matrix,
            xx = matrix.elements[0],
            yy = matrix.elements[3],
            dx = matrix.elements[4],
            dy = surface.roundPixel(matrix.elements[5]) - 1,
            maxBarWidth = Math.abs(xx) - attr.minGapWidth,
            minBarWidth = (Math.min(maxBarWidth, attr.maxBarWidth) -
                           inGroupGapWidth * (groupCount - 1)) / groupCount,
            barWidth = surface.roundPixel(Math.max(attr.minBarWidth, minBarWidth)),
            surfaceMatrix = me.surfaceMatrix,
            left, right, bottom, top, i, center,
            halfLineWidth = 0.5 * attr.lineWidth,
            // Finding min/max so that bars render properly in both LTR and RTL modes.
            min = Math.min(dataClipRect[0], dataClipRect[2]),
            max = Math.max(dataClipRect[0], dataClipRect[2]),
            start = Math.max(0, Math.floor(min)),
            end = Math.min(dataX.length - 1, Math.ceil(max)),
            isDrawLabels = dataText && me.getMarker('labels'),
            yLow, yHi;

        // The scaling (xx) and translation (dx) here will already be such that the midpoints
        // of the first and last bars are not at the surface edges (which would mean that
        // bars are half-clipped), but padded, so that those bars are fully visible
        // (assuming no pan/zoom).
        for (i = start; i <= end; i++) {
            yLow = dataStartY ? dataStartY[i] : 0;
            yHi = dataY[i];
            center = dataX[i] * xx + dx + groupOffset * (barWidth + inGroupGapWidth);
            left = surface.roundPixel(center - barWidth / 2) + halfLineWidth;
            top = surface.roundPixel(yHi * yy + dy + lineWidth);
            right = surface.roundPixel(center + barWidth / 2) - halfLineWidth;
            bottom = surface.roundPixel(yLow * yy + dy + lineWidth);

            me.drawBar(ctx, surface, dataClipRect, left, top - halfLineWidth, right,
                       bottom - halfLineWidth, i);

            // We want 0 values to be passed to the renderer
            if (isDrawLabels && dataText[i] != null) {
                me.drawLabel(dataText[i], center, bottom, top, i);
            }

            me.putMarker('markers', {
                translationX: surfaceMatrix.x(center, top),
                translationY: surfaceMatrix.y(center, top)
            }, i, true);
        }
    }
});
