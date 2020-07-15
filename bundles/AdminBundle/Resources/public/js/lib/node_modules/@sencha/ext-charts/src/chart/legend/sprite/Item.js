/**
 * @private
 */
Ext.define('Ext.chart.legend.sprite.Item', {
    extend: 'Ext.draw.sprite.Composite',
    alias: 'sprite.legenditem',
    type: 'legenditem',
    isLegendItem: true,

    requires: [
        'Ext.draw.sprite.Text',
        'Ext.draw.sprite.Circle'
    ],

    inheritableStatics: {
        def: {
            processors: {
                enabled: 'limited01',
                markerLabelGap: 'number'
            },
            animationProcessors: {
                enabled: null,
                markerLabelGap: null
            },
            defaults: {
                enabled: true,
                markerLabelGap: 5
            },
            triggers: {
                enabled: 'enabled',
                markerLabelGap: 'layout'
            },
            updaters: {
                layout: 'layoutUpdater',
                enabled: 'enabledUpdater'
            }
        }
    },

    config: {
        // Sprite's attributes are processed after initConfig.
        // So we need to init below configs lazily, as otherwise
        // adding sprites (created from those configs) to composite
        // will result in an attempt to access attributes that
        // composite doesn't have yet.
        label: {
            $value: {
                type: 'text'
            },
            lazy: true
        },
        marker: {
            $value: {
                type: 'circle'
            },
            lazy: true
        },

        legend: null,
        store: null,
        record: null,
        series: null
    },

    applyLabel: function(label, oldLabel) {
        var sprite;

        if (label) {
            if (label.isSprite && label.type === 'text') {
                sprite = label;
            }
            else {
                if (oldLabel && label.type === oldLabel.type) {
                    oldLabel.setConfig(label);
                    sprite = oldLabel;
                    this.scheduleUpdater(this.attr, 'layout');
                }
                else {
                    sprite = new Ext.draw.sprite.Text(label);
                }
            }
        }

        return sprite;
    },

    defaultMarkerSize: 10,

    updateLabel: function(label, oldLabel) {
        var me = this;

        me.removeSprite(oldLabel);
        label.setAttributes({
            textBaseline: 'middle'
        });
        me.addSprite(label);
        me.scheduleUpdater(me.attr, 'layout');
    },

    applyMarker: function(config) {
        var marker;

        if (config) {
            if (config.isSprite) {
                marker = config;
            }
            else {
                marker = this.createMarker(config);
            }
        }

        marker = this.resetMarker(marker, config);

        return marker;
    },

    createMarker: function(config) {
        var marker;

        // If marker attributes are animated, the attributes change over
        // time from default values to the values specified in the marker
        // config. But the 'legenditem' sprite needs final values
        // to properly layout its children.
        delete config.animation;

        if (config.type === 'image') {
            delete config.width;
            delete config.height;
        }

        marker = Ext.create('sprite.' + config.type, config);

        return marker;
    },

    resetMarker: function(sprite, config) {
        var size = config.size || this.defaultMarkerSize,
            bbox, max, scale;

        // Layout may not work properly,
        // if the marker sprite is transformed to begin with.
        sprite.setTransform([1, 0, 0, 1, 0, 0], true);

        if (config.type === 'image') {
            sprite.setAttributes({
                width: size,
                height: size
            });
        }
        else {
            // This should work with any sprite, irrespective of what attribute
            // is used to control sprite's size ('size', 'r', or something else).
            // However, the 'image' sprite above is a special case.
            bbox = sprite.getBBox();
            max = Math.max(bbox.width, bbox.height);
            scale = size / max;
            sprite.setAttributes({
                scalingX: scale,
                scalingY: scale
            });
        }

        return sprite;
    },

    updateMarker: function(marker, oldMarker) {
        var me = this;

        me.removeSprite(oldMarker);
        me.addSprite(marker);
        me.scheduleUpdater(me.attr, 'layout');
    },

    updateSurface: function(surface, oldSurface) {
        var me = this;

        me.callParent([surface, oldSurface]);

        if (surface) {
            me.scheduleUpdater(me.attr, 'layout');
        }
    },

    enabledUpdater: function(attr) {
        var marker = this.getMarker();

        if (marker) {
            marker.setAttributes({
                globalAlpha: attr.enabled ? 1 : 0.3
            });
        }
    },

    layoutUpdater: function() {
        var me = this,
            attr = me.attr,
            label = me.getLabel(),
            marker = me.getMarker(),
            labelBBox, markerBBox,
            totalHeight;

        // Measuring bounding boxes of transformed marker and label
        // sprites and translating the sprites by required amount,
        // makes layout virtually bullet-proof to unaccounted for
        // changes in sprite attributes, whatever the sprite type may be.

        markerBBox = marker.getBBox();
        labelBBox = label.getBBox();

        totalHeight = Math.max(markerBBox.height, labelBBox.height);

        // Because we are getting an already transformed bounding box,
        // we want to add to that transformation, not replace it,
        // so setting translationX/Y attributes here would be inappropriate.
        marker.transform([1, 0, 0, 1,
                          -markerBBox.x,
                          -markerBBox.y + (totalHeight - markerBBox.height) / 2
        ], true);
        label.transform([1, 0, 0, 1,
                         -labelBBox.x + markerBBox.width + attr.markerLabelGap,
                         -labelBBox.y + (totalHeight - labelBBox.height) / 2
        ], true);

        me.bboxUpdater(attr);
    }

});
