/**
 * @class Ext.chart.series.sprite.PieSlice
 *
 * Pie slice sprite.
 */
Ext.define('Ext.chart.series.sprite.PieSlice', {
    extend: 'Ext.draw.sprite.Sector',
    mixins: {
        markerHolder: 'Ext.chart.MarkerHolder'
    },
    alias: 'sprite.pieslice',

    inheritableStatics: {
        def: {
            processors: {
                /**
                 * @cfg {Boolean} [doCallout=true]
                 * 'true' if the pie series uses label callouts.
                 */
                doCallout: 'bool',

                /**
                 * @cfg {String} [label='']
                 * Label associated with the Pie sprite.
                 */
                label: 'string',

                // @deprecated Use series.label.orientation config instead.
                // @since 5.0.1
                rotateLabels: 'bool',

                /**
                 * @cfg {Number} [labelOverflowPadding=10]
                 * Padding around labels to determine overlap.
                 * Any negative number allows the labels to overlap.
                 */
                labelOverflowPadding: 'number',

                renderer: 'default'
            },
            defaults: {
                doCallout: true,
                rotateLabels: true,
                label: '',
                labelOverflowPadding: 10,
                renderer: null
            }
        }
    },

    config: {
        /**
         * @private
         * @cfg {Object} rendererData The object that is passed to the renderer.
         *
         * For instance when the PieSlice sprite is used in a Gauge chart, the object
         * contains the 'store' and 'angleField' properties, and the 'value' as well
         * for that one PieSlice that is used to draw the needle of the Gauge.
         */
        rendererData: null,
        rendererIndex: 0,
        series: null
    },

    setGradientBBox: function(ctx, rect) {
        var me = this,
            attr = me.attr,
            hasGradients = (attr.fillStyle && attr.fillStyle.isGradient) ||
                           (attr.strokeStyle && attr.strokeStyle.isGradient);

        if (hasGradients && !attr.constrainGradients) {
            // eslint-disable-next-line vars-on-top, one-var
            var midAngle = me.getMidAngle(),
                margin = attr.margin,
                cx = attr.centerX,
                cy = attr.centerY,
                r = attr.endRho,
                matrix = attr.matrix,
                scaleX = matrix.getScaleX(),
                scaleY = matrix.getScaleY(),
                w = scaleX * r,
                h = scaleY * r,
                bbox = {
                    width: w + w,
                    height: h + h
                };

            if (margin) {
                cx += margin * Math.cos(midAngle);
                cy += margin * Math.sin(midAngle);
            }

            bbox.x = matrix.x(cx, cy) - w;
            bbox.y = matrix.y(cx, cy) - h;
            ctx.setGradientBBox(bbox);
        }
        else {
            me.callParent([ctx, rect]);
        }
    },

    render: function(surface, ctx, rect) {
        var me = this,
            attr = me.attr,
            itemCfg = {},
            changes;

        if (attr.renderer) {
            itemCfg = {
                type: 'sector',
                centerX: attr.centerX,
                centerY: attr.centerY,
                margin: attr.margin,
                startAngle: Math.min(attr.startAngle, attr.endAngle),
                endAngle: Math.max(attr.startAngle, attr.endAngle),
                startRho: Math.min(attr.startRho, attr.endRho),
                endRho: Math.max(attr.startRho, attr.endRho)
            };

            changes = Ext.callback(attr.renderer, null,
                                   [me, itemCfg, me.getRendererData(), me.getRendererIndex()],
                                   0, me.getSeries());

            me.setAttributes(changes);
            me.useAttributes(ctx, rect);
        }

        // Draw the sector
        me.callParent([surface, ctx, rect]);

        // Draw the labels
        if (attr.label && me.getMarker('labels')) {
            me.placeLabel();
        }
    },

    placeLabel: function() {
        var me = this,
            attr = me.attr,
            attributeId = attr.attributeId,
            startAngle = Math.min(attr.startAngle, attr.endAngle),
            endAngle = Math.max(attr.startAngle, attr.endAngle),
            midAngle = (startAngle + endAngle) * 0.5,
            margin = attr.margin,
            centerX = attr.centerX,
            centerY = attr.centerY,
            sinMidAngle = Math.sin(midAngle),
            cosMidAngle = Math.cos(midAngle),
            startRho = Math.min(attr.startRho, attr.endRho) + margin,
            endRho = Math.max(attr.startRho, attr.endRho) + margin,
            midRho = (startRho + endRho) * 0.5,
            surfaceMatrix = me.surfaceMatrix,
            labelCfg = me.labelCfg || (me.labelCfg = {}),
            label = me.getMarker('labels'),
            labelTpl = label.getTemplate(),
            hideLessThan = labelTpl.getHideLessThan(),
            calloutLine = labelTpl.getCalloutLine(),
            labelBox, x, y, changes, params, calloutLineLength;

        if (calloutLine) {
            calloutLineLength = calloutLine.length || 40;
        }
        else {
            calloutLineLength = 0;
        }

        surfaceMatrix.appendMatrix(attr.matrix);

        labelCfg.text = attr.label;

        x = centerX + cosMidAngle * midRho;
        y = centerY + sinMidAngle * midRho;
        labelCfg.x = surfaceMatrix.x(x, y);
        labelCfg.y = surfaceMatrix.y(x, y);

        x = centerX + cosMidAngle * endRho;
        y = centerY + sinMidAngle * endRho;
        labelCfg.calloutStartX = surfaceMatrix.x(x, y);
        labelCfg.calloutStartY = surfaceMatrix.y(x, y);

        x = centerX + cosMidAngle * (endRho + calloutLineLength);
        y = centerY + sinMidAngle * (endRho + calloutLineLength);
        labelCfg.calloutPlaceX = surfaceMatrix.x(x, y);
        labelCfg.calloutPlaceY = surfaceMatrix.y(x, y);

        if (!attr.rotateLabels) {
            labelCfg.rotationRads = 0;
            //<debug>
            Ext.log.warn("'series.style.rotateLabels' config is deprecated. " +
                         "Use 'series.label.orientation' config instead.");
            //</debug>
        }
        else {
            switch (labelTpl.attr.orientation) {
                case 'horizontal':
                    labelCfg.rotationRads = midAngle + Math.atan2(
                        surfaceMatrix.y(1, 0) - surfaceMatrix.y(0, 0),
                        surfaceMatrix.x(1, 0) - surfaceMatrix.x(0, 0)
                    ) + Math.PI / 2;
                    break;

                case 'vertical':
                    labelCfg.rotationRads = midAngle + Math.atan2(
                        surfaceMatrix.y(1, 0) - surfaceMatrix.y(0, 0),
                        surfaceMatrix.x(1, 0) - surfaceMatrix.x(0, 0)
                    );
                    break;
            }
        }

        labelCfg.calloutColor = (calloutLine && calloutLine.color) || me.attr.fillStyle;

        if (calloutLine) {
            if (calloutLine.width) {
                labelCfg.calloutWidth = calloutLine.width;
            }
        }
        else {
            labelCfg.calloutColor = 'none';
        }

        labelCfg.globalAlpha = attr.globalAlpha * attr.fillOpacity;

        // If a slice is empty, don't display the label.
        // This behavior can be overridden by a renderer.
        if (labelTpl.display !== 'none') {
            // eslint-disable-next-line eqeqeq
            labelCfg.hidden = (attr.startAngle == attr.endAngle);
        }

        if (labelTpl.attr.renderer) {
            // Note: the labels are 'put' by the Ext.chart.series.Pie.updateLabelData, so we can
            // be sure the label sprite instances will exist and can be accessed from the label
            // renderer on first render. For example, with 'bar' series this isn't the case,
            // so we make a check and create a label instance if necessary.
            params = [me.attr.label, label, labelCfg, me.getRendererData(), me.getRendererIndex()];
            changes = Ext.callback(labelTpl.attr.renderer, null, params, 0, me.getSeries());

            if (typeof changes === 'string') {
                labelCfg.text = changes;
            }
            else {
                Ext.apply(labelCfg, changes);
            }
        }

        me.putMarker('labels', labelCfg, attributeId);

        labelBox = me.getMarkerBBox('labels', attributeId, true);

        if (labelBox) {
            if (attr.doCallout &&
                ((endAngle - startAngle) * endRho > hideLessThan || attr.highlighted)) {
                if (labelTpl.attr.display === 'outside') {
                    me.putMarker('labels', {
                        callout: 1
                    }, attributeId);
                }
                else if (labelTpl.attr.display === 'inside') {
                    me.putMarker('labels', {
                        callout: 0
                    }, attributeId);
                }
                else {
                    me.putMarker('labels', {
                        callout: 1 - me.sliceContainsLabel(attr, labelBox)
                    }, attributeId);
                }
            }
            else {
                me.putMarker('labels', {
                    globalAlpha: me.sliceContainsLabel(attr, labelBox)
                }, attributeId);
            }
        }
    },

    sliceContainsLabel: function(attr, bbox) {
        var padding = attr.labelOverflowPadding,
            middle = (attr.endRho + attr.startRho) / 2,
            outer = middle + (bbox.width + padding) / 2,
            inner = middle - (bbox.width + padding) / 2,
            sliceAngle, l1, l2, l3;

        if (padding < 0) {
            return 1;
        }

        if (bbox.width + padding * 2 > (attr.endRho - attr.startRho)) {
            return 0;
        }

        l1 = Math.sqrt(attr.endRho * attr.endRho - outer * outer);
        l2 = Math.sqrt(attr.endRho * attr.endRho - inner * inner);
        sliceAngle = Math.abs(attr.endAngle - attr.startAngle);
        l3 = (sliceAngle > Math.PI / 2 ? inner : Math.abs(Math.tan(sliceAngle / 2)) * inner);

        if (bbox.height + padding * 2 > Math.min(l1, l2, l3) * 2) {
            return 0;
        }

        return 1;
    }
});
