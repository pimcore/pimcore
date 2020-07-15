/**
 * This class uses `Ext.draw.sprite.Sprite` to render the chart legend.
 *
 * The DOM legend is essentially a data view docked inside a draw container, which a chart is.
 * The sprite legend, on the other hand, is not a foreign entity in a draw container,
 * and is rendered in a draw surface with sprites, just like series and axes.
 *
 * This means that:
 *
 * * it is styleable with chart themes
 * * it shows up in chart preview and chart download
 * * it renders markers exactly as they are in the series
 * * it can't be styled with CSS
 * * it doesn't scroll, instead the items are grouped into columns,
 *   and the legend grows in size as the number of items increases
 *
 */
Ext.define('Ext.chart.legend.SpriteLegend', {
    alias: 'legend.sprite',
    type: 'sprite',
    isLegend: true,
    isSpriteLegend: true,

    mixins: [
        'Ext.mixin.Observable'
    ],

    requires: [
        'Ext.chart.legend.sprite.Item',
        'Ext.chart.legend.sprite.Border',
        'Ext.draw.overrides.hittest.All',
        'Ext.draw.Animator'
    ],

    config: {
        /**
         * @cfg {'top'/'left'/'right'/'bottom'} docked
         * The position of the legend in the chart.
         */
        docked: 'bottom',

        /**
         * @cfg {Ext.chart.legend.store.Store} store
         * The {@link Ext.chart.legend.store.Store} to bind this legend to.
         * @private
         */
        store: null,

        /**
         * @cfg {Ext.chart.AbstractChart} chart
         * The chart that the store belongs to.
         */
        chart: null,

        /**
         * @cfg {Ext.draw.Surface} surface
         * The chart surface used to render legend sprites.
         * @protected
         */
        surface: null,

        /**
         * @cfg {Object} size
         * The size of the area occupied by the legend's sprites.
         * This is set by the legend itself and then used during chart layout
         * to make sure the 'legend' surface is big enough to accommodate
         * legend sprites.
         * @cfg {Number} size.width
         * @cfg {Number} size.height
         * @readonly
         */
        size: {
            width: 0,
            height: 0
        },

        /**
         * @cfg {Boolean} toggleable
         * `true` to allow series items to have their visibility
         * toggled by interaction with the legend items.
         */
        toggleable: true,

        /**
         * @cfg {Number} padding
         * The padding amount between legend items and legend border.
         */
        padding: 10,

        label: {
            preciseMeasurement: true
        },

        /**
         * The sprite to use as a legend item marker. By default a corresponding series
         * marker is used. If the series has no marker, the `circle` sprite
         * is used as a legend item marker, where its `fillStyle`, `strokeStyle` and
         * `lineWidth` match that of the series. The size of a legend item marker is
         * controlled by the `size` property, which to defaults to `10` (pixels).
         */
        marker: {
        },

        /**
         * @cfg {Object} border
         * The border that goes around legend item sprites.
         * The type of the sprite is determined by this config,
         * while the styling comes from a theme {@link Ext.chart.theme.Base #legend}.
         * If both this config and the theme provide values for the
         * same configs, the values from this config are used.
         * The sprite class used a legend border should have the `isLegendBorder`
         * property set to true on the prototype. The legend border sprite
         * should also have the `x`, `y`, `width` and `height` attributes
         * that determine it's position and dimensions.
         */
        border: {
            $value: {
                type: 'legendborder'
            },
            // The config should be processed at the time of the 'getSprites' call,
            // when we already have the legend surface, otherwise the border sprite
            // will not be added to the surface.
            lazy: true
        },

        /**
         * @cfg {Object} background
         * Sets the legend background.
         * This can be a gradient object, image, or color. This config works similarly
         * to the {@link Ext.chart.AbstractChart#background} config.
         */
        background: null,

        /**
         * @cfg {Boolean} hidden Toggles the visibility of the legend.
         */
        hidden: false
    },

    sprites: null,

    spriteZIndexes: {
        background: 0,
        border: 1,
        // Item sprites should have a higher zIndex than border,
        // or they won't react to clicks.
        item: 2
    },

    dockedValues: {
        left: true,
        right: true,
        top: true,
        bottom: true
    },

    constructor: function(config) {
        var me = this;

        me.oldSize = {
            width: 0,
            height: 0
        };
        me.getId();
        me.mixins.observable.constructor.call(me, config);
    },

    applyStore: function(store) {
        return store && Ext.StoreManager.lookup(store);
    },

    updateStore: function(store, oldStore) {
        var me = this;

        if (oldStore) {
            oldStore.un('datachanged', me.onDataChanged, me);
            oldStore.un('update', me.onDataUpdate, me);
        }

        if (store) {
            store.on('datachanged', me.onDataChanged, me);
            store.on('update', me.onDataUpdate, me);
            me.onDataChanged(store);
        }

        me.performLayout();
    },

    //<debug>
    applyDocked: function(docked) {
        if (!(docked in this.dockedValues)) {
            Ext.raise("Invalid 'docked' config value.");
        }

        return docked;
    },
    //</debug>

    updateDocked: function(docked) {
        this.isTop = docked === 'top';

        if (!this.isConfiguring) {
            this.layoutChart();
        }
    },

    updateHidden: function(hidden) {
        var surface;

        this.getChart(); // 'chart' updater will set the surface

        surface = this.getSurface();

        if (surface) {
            surface.setHidden(hidden);
        }

        if (!this.isConfiguring) {
            this.layoutChart();
        }
    },

    /**
     * @private
     */
    layoutChart: function() {
        var chart;

        if (!this.isConfiguring) {
            chart = this.getChart();

            if (chart) {
                chart.scheduleLayout();
            }
        }
    },

    /**
     * @private
     * Calculates and returns the legend surface rect and adjusts the passed `chartRect`
     * accordingly. The first time this is called, the `SpriteLegend` will have zero size
     * (no width or height).
     * @param {Number[]} chartRect [left, top, width, height] components as an array.
     * @return {Number[]} [left, top, width, height] components as an array, or null.
     */
    computeRect: function(chartRect) {
        var rect, docked, size, height, width;

        if (this.getHidden()) {
            return null;
        }

        rect = [0, 0, 0, 0];
        docked = this.getDocked();
        size = this.getSize();
        height = size.height;
        width = size.width;

        switch (docked) {
            case 'top':
                rect[1] = chartRect[1];
                rect[2] = chartRect[2];
                rect[3] = height;
                chartRect[1] += height;
                chartRect[3] -= height;
                break;

            case 'bottom':
                chartRect[3] -= height;
                rect[1] = chartRect[3];
                rect[2] = chartRect[2];
                rect[3] = height;
                break;

            case 'left':
                chartRect[0] += width;
                chartRect[2] -= width;
                rect[2] = width;
                rect[3] = chartRect[3];
                break;

            case 'right':
                chartRect[2] -= width;
                rect[0] = chartRect[2];
                rect[2] = width;
                rect[3] = chartRect[3];
                break;
        }

        return rect;
    },

    applyBorder: function(config) {
        var border;

        if (config) {
            if (config.isSprite) {
                border = config;
            }
            else {
                border = Ext.create('sprite.' + config.type, config);
            }
        }

        if (border) {
            border.isLegendBorder = true;
            border.setAttributes({
                zIndex: this.spriteZIndexes.border
            });
        }

        return border;
    },

    updateBorder: function(border, oldBorder) {
        var surface = this.getSurface();

        this.borderSprite = null;

        if (surface) {
            if (oldBorder) {
                surface.remove(oldBorder);
            }

            if (border) {
                this.borderSprite = surface.add(border);
            }
        }
    },

    scheduleLayout: function() {
        if (!this.scheduledLayoutId) {
            this.scheduledLayoutId = Ext.draw.Animator.schedule('performLayout', this);
        }
    },

    cancelLayout: function() {
        Ext.draw.Animator.cancel(this.scheduledLayoutId);
        this.scheduledLayoutId = null;
    },

    performLayout: function() {
        var me = this,
            size = me.getSize(),
            gap = me.getPadding(),
            sprites = me.getSprites(),
            surface = me.getSurface(),
            background = me.getBackground(),
            surfaceRect = surface.getRect(),
            store = me.getStore(),
            ln = (sprites && sprites.length) || 0,
            i, sprite;

        if (!surface || !surfaceRect || !store) {
            return false;
        }

        me.cancelLayout();

        // eslint-disable-next-line vars-on-top, one-var
        var docked = me.getDocked(),
            surfaceWidth = surfaceRect[2],
            surfaceHeight = surfaceRect[3],
            border = me.borderSprite,
            bboxes = [],
            startX,      // Coordinates of the top-left corner.
            startY,      // of the first 'legenditem' sprite.
            columnSize,  // Number of items in a column.
            columnCount, // Number of columns.
            columnWidth,
            itemsWidth,
            itemsHeight,
            paddedItemsWidth,  // The horizontal span of all 'legenditem' sprites.
            paddedItemsHeight, // The vertical span of all 'legenditem' sprites.
            paddedBorderWidth,
            paddedBorderHeight, // eslint-disable-line no-unused-vars
            itemHeight,
            bbox, x, y;

        for (i = 0; i < ln; i++) {
            sprite = sprites[i];
            bbox = sprite.getBBox();
            bboxes.push(bbox);
        }

        if (bbox) {
            itemHeight = bbox.height;
        }

        switch (docked) {
            /*

             Horizontal legend.
             The outer box is the legend surface.
             The inner box is the legend border.
             There's a fixed amount of padding between all the items,
             denoted by ##. This amount is controlled by the 'padding' config
             of the legend.

             |-------------------------------------------------------------|
             |                             ##                              |
             |    |---------------------------------------------------|    |
             |    |        ##              ##               ##        |    |
             |    |     --------        -----------      --------     |    |
             | ## | ## | Item 0 |   ## | Item 2    | ## | Item 4 | ## | ## |
             |    |     --------        -----------      --------     |    |
             |    |        ##              ##               ##        |    |
             |    |     ----------      ---------                     |    |
             |    | ## | Item 1   | ## | Item 3  |                    |    |
             |    |     ----------      ---------                     |    |
             |    |        ##              ##                         |    |
             |    |---------------------------------------------------|    |
             |                             ##                              |
             |-------------------------------------------------------------|

             */
            case 'bottom':
            case 'top':

                // surface must have a width before we can proceed to layout top/bottom
                // docked legend.  width may be 0 if we are rendered into an inactive tab.
                // see https://sencha.jira.com/browse/EXTJS-22454
                if (!surfaceWidth) {
                    return false;
                }

                columnSize = 0;

                // Split legend items into columns until the width is suitable.
                do {
                    itemsWidth = 0;
                    columnWidth = 0;
                    columnCount = 0;

                    columnSize++;

                    for (i = 0; i < ln; i++) {
                        bbox = bboxes[i];

                        if (bbox.width > columnWidth) {
                            columnWidth = bbox.width;
                            // if the column width is greater than available surface width,
                            // set it to maximum available width
                            columnWidth = Math.min(columnWidth, surfaceWidth - (gap * 4));
                        }

                        if ((i + 1) % columnSize === 0) {
                            itemsWidth += columnWidth;
                            columnWidth = 0;
                            columnCount++;
                        }
                    }

                    if (i % columnSize !== 0) {
                        itemsWidth += columnWidth;
                        columnCount++;
                    }

                    paddedItemsWidth = itemsWidth + (columnCount - 1) * gap;
                    paddedBorderWidth = paddedItemsWidth + gap * 4;

                } while (paddedBorderWidth > surfaceWidth);

                paddedItemsHeight = itemHeight * columnSize + (columnSize - 1) * gap;

                break;

                /*

             Vertical legend.

             |-----------------------------------------------|
             |                     ##                        |
             |    |-------------------------------------|    |
             |    |        ##               ##          |    |
             |    |     --------        -----------     |    |
             |    | ## | Item 0 |   ## | Item 1    | ## |    |
             |    |     --------        -----------     |    |
             |    |        ##               ##          |    |
             |    |     ----------      ---------       |    |
             | ## | ## | Item 2   | ## | Item 3  |      | ## |
             |    |     ----------      ---------       |    |
             |    |        ##                           |    |
             |    |     --------                        |    |
             |    | ## | Item 4 |                       |    |
             |    |     --------                        |    |
             |    |        ##                           |    |
             |    |-------------------------------------|    |
             |                     ##                        |
             |-----------------------------------------------|

             */

            case 'right':
            case 'left':
                // surface must have a height before we can proceed to layout right/left
                // docked legend.  height may be 0 if we are rendered into an inactive tab.
                // see https://sencha.jira.com/browse/EXTJS-22454
                if (!surfaceHeight) {
                    return false;
                }

                columnSize = ln * 2;

                // Split legend items into columns until the height is suitable.
                do {
                    // Integer division by 2, plus remainder.
                    columnSize = (columnSize >> 1) + (columnSize % 2);

                    itemsWidth = 0;
                    itemsHeight = 0;
                    columnWidth = 0;
                    columnCount = 0;

                    for (i = 0; i < ln; i++) {
                        bbox = bboxes[i];

                        // itemsHeight is determined by the height of the first column.
                        if (!columnCount) {
                            itemsHeight += bbox.height;
                        }

                        if (bbox.width > columnWidth) {
                            columnWidth = bbox.width;
                        }

                        if ((i + 1) % columnSize === 0) {
                            itemsWidth += columnWidth;
                            columnWidth = 0;
                            columnCount++;
                        }
                    }

                    if (i % columnSize !== 0) {
                        itemsWidth += columnWidth;
                        columnCount++;
                    }

                    paddedItemsWidth = itemsWidth + (columnCount - 1) * gap;
                    paddedItemsHeight = itemsHeight + (columnSize - 1) * gap;
                    paddedBorderWidth = paddedItemsWidth + gap * 4;
                    paddedBorderHeight = paddedItemsHeight + gap * 4;

                } while (paddedItemsHeight > surfaceHeight);

                break;

        }

        startX = (surfaceWidth - paddedItemsWidth) / 2;
        startY = (surfaceHeight - paddedItemsHeight) / 2;

        x = 0;
        y = 0;
        columnWidth = 0;

        for (i = 0; i < ln; i++) {
            sprite = sprites[i];
            bbox = bboxes[i];
            sprite.setAttributes({
                translationX: startX + x,
                translationY: startY + y
            });

            if (bbox.width > columnWidth) {
                columnWidth = bbox.width;
            }

            if ((i + 1) % columnSize === 0) {
                x += columnWidth + gap;
                y = 0;
                columnWidth = 0;
            }
            else {
                y += bbox.height + gap;
            }
        }

        if (border) {
            border.setAttributes({
                hidden: !ln,
                x: startX - gap,
                y: startY - gap,
                width: paddedItemsWidth + gap * 2,
                height: paddedItemsHeight + gap * 2
            });
        }

        size.width = border.attr.width + gap * 2;
        size.height = border.attr.height + gap * 2;

        if (size.width !== me.oldSize.width || size.height !== me.oldSize.height) {
            // Do not simply assign size to oldSize, as we want them to be
            // separate objects.
            Ext.apply(me.oldSize, size);
            // Legend size has changed, so we return 'false' to cancel the current
            // chart layout (this method is called by chart's 'performLayout' method)
            // and manually start a new chart layout.
            me.getChart().scheduleLayout();

            return false;
        }

        if (background) {
            me.resizeBackground(surface, background);
        }

        surface.renderFrame();

        return true;
    },

    // Doesn't include the border sprite which also belongs to the 'legend'
    // surface. To get it, use the 'getBorder' method.
    getSprites: function() {
        this.updateSprites();

        return this.sprites;
    },

    /**
     * @private
     * Creates a 'legenditem' sprite in the given surface
     * using the legend store record data provided.
     * @param {Ext.draw.Surface} surface
     * @param {Ext.chart.legend.store.Item} record
     * @return {Ext.chart.legend.sprite.Item}
     */
    createSprite: function(surface, record) {
        var me = this,
            data = record.data,
            chart = me.getChart(),
            series = chart.get(data.series),
            seriesMarker = series.getMarker(),
            sprite = null,
            markerConfig, labelConfig, legendItemConfig;

        if (surface) {
            markerConfig = series.getMarkerStyleByIndex(data.index);
            markerConfig.fillStyle = data.mark;
            markerConfig.hidden = false;

            if (seriesMarker && seriesMarker.type) {
                markerConfig.type = seriesMarker.type;
            }

            Ext.apply(markerConfig, me.getMarker());
            markerConfig.surface = surface;
            labelConfig = me.getLabel();

            legendItemConfig = {
                type: 'legenditem',
                zIndex: me.spriteZIndexes.item,
                text: data.name,
                enabled: !data.disabled,
                marker: markerConfig,
                label: labelConfig,
                series: data.series,
                record: record
            };

            sprite = surface.add(legendItemConfig);
        }

        return sprite;
    },

    /**
     * @private
     * Creates legend item sprites and associates them with legend store records.
     * Updates attributes of the sprites when legend store data changes.
     */
    updateSprites: function() {
        var me = this,
            chart = me.getChart(),
            store = me.getStore(),
            surface = me.getSurface(),
            item, items, itemSprite,
            i, ln, sprites, unusedSprites,
            border;

        if (!(chart && store && surface)) {
            return;
        }

        me.sprites = sprites = me.sprites || [];
        items = store.getData().items;
        ln = items.length;

        for (i = 0; i < ln; i++) {
            item = items[i];
            itemSprite = sprites[i];

            if (itemSprite) {
                me.updateSprite(itemSprite, item);
            }
            else {
                itemSprite = me.createSprite(surface, item);
                surface.add(itemSprite);
                sprites.push(itemSprite);
            }
        }

        unusedSprites = Ext.Array.splice(sprites, i, sprites.length);

        for (i = 0, ln = unusedSprites.length; i < ln; i++) {
            itemSprite = unusedSprites[i];
            itemSprite.destroy();
        }

        border = me.getBorder();

        if (border) {
            me.borderSprite = border;
        }

        me.updateTheme(chart.getTheme());
    },

    /**
     * @private
     * Updates the given legend item sprite based on store record data.
     * @param {Ext.chart.legend.sprite.Item} sprite
     * @param {Ext.chart.legend.store.Item} record
     */
    updateSprite: function(sprite, record) {
        var data = record.data,
            chart = this.getChart(),
            series = chart.get(data.series),
            marker, label, markerConfig;

        if (sprite) {
            label = sprite.getLabel();
            label.setAttributes({
                text: data.name
            });

            sprite.setAttributes({
                enabled: !data.disabled
            });
            sprite.setConfig({
                series: data.series,
                record: record
            });

            markerConfig = series.getMarkerStyleByIndex(data.index);
            markerConfig.fillStyle = data.mark;
            markerConfig.hidden = false;
            Ext.apply(markerConfig, this.getMarker());
            marker = sprite.getMarker();
            marker.setAttributes({
                fillStyle: markerConfig.fillStyle,
                strokeStyle: markerConfig.strokeStyle
            });
            sprite.layoutUpdater(sprite.attr);
        }
    },

    updateChart: function(newChart, oldChart) {
        var me = this;

        if (oldChart) {
            me.setSurface(null);
        }

        if (newChart) {
            me.setSurface(newChart.getSurface('legend'));
        }
    },

    updateSurface: function(surface, oldSurface) {
        if (oldSurface) {
            oldSurface.el.un('click', 'onClick', this);
            // The surface should not be destroyed here, just cleared.
            // E.g. we may remove the sprite legend only to add another one.
            oldSurface.removeAll(true);
        }

        if (surface) {
            surface.isLegendSurface = true;
            surface.el.on('click', 'onClick', this);
        }
    },

    onClick: function(event) {
        var chart = this.getChart(),
            surface = this.getSurface(),
            result, point;

        if (chart && chart.hasFirstLayout && surface) {
            point = surface.getEventXY(event);
            result = surface.hitTest(point);

            if (result && result.sprite) {
                this.toggleItem(result.sprite);
            }
        }
    },

    applyBackground: function(newBackground, oldBackground) {
        var me = this,
            // It's important to get the `chart` first here,
            // because the `surface` is set by the `chart` updater.
            chart = me.getChart(),
            surface = me.getSurface(),
            background;

        background = chart.refreshBackground(surface, newBackground, oldBackground);

        if (background) {
            background.setAttributes({
                zIndex: me.spriteZIndexes.background
            });
        }

        return background;
    },

    resizeBackground: function(surface, background) {
        var width = background.attr.width,
            height = background.attr.height,
            surfaceRect = surface.getRect();

        if (surfaceRect && (width !== surfaceRect[2] || height !== surfaceRect[3])) {
            background.setAttributes({
                width: surfaceRect[2],
                height: surfaceRect[3]
            });
        }
    },

    themeableConfigs: {
        background: true
    },

    updateTheme: function(theme) {
        var me = this,
            surface = me.getSurface(),
            sprites = surface.getItems(),
            legendTheme = theme.getLegend(),
            labelConfig = me.getLabel(),
            configs = me.self.getConfigurator().configs,
            themeableConfigs = me.themeableConfigs,
            themeOnlyIfConfigured = me.themeOnlyIfConfigured,
            initialConfig = me.getInitialConfig(),
            defaultConfig = me.defaultConfig,
            value, cfg, isObjValue, isUnusedConfig, initialValue,
            sprite, style, labelSprite,
            key, attr,
            i, ln;

        for (i = 0, ln = sprites.length; i < ln; i++) {
            sprite = sprites[i];

            if (sprite.isLegendItem) {
                style = legendTheme.label;

                if (style) {
                    attr = null;

                    for (key in style) {
                        if (!(key in labelConfig)) {
                            attr = attr || {};
                            attr[key] = style[key];
                        }
                    }

                    if (attr) {
                        labelSprite = sprite.getLabel();
                        labelSprite.setAttributes(attr);
                    }
                }
                continue;
            }
            else if (sprite.isLegendBorder) {
                style = legendTheme.border;
            }
            else {
                continue;
            }

            if (style) {
                attr = {};

                for (key in style) {
                    if (!(key in sprite.config)) {
                        attr[key] = style[key];
                    }
                }

                sprite.setAttributes(attr);
            }
        }

        value = legendTheme.background;
        cfg = configs.background;

        if (value !== null && value !== undefined && cfg) {
            // TODO
        }

        for (key in legendTheme) {
            if (!(key in themeableConfigs)) {
                continue;
            }

            value = legendTheme[key];
            cfg = configs[key];

            if (value !== null && value !== undefined && cfg) {
                initialValue = initialConfig[key];
                isObjValue = Ext.isObject(value);
                isUnusedConfig = initialValue === defaultConfig[key];

                if (isObjValue) {
                    if (isUnusedConfig && themeOnlyIfConfigured[key]) {
                        continue;
                    }

                    value = Ext.merge({}, value, initialValue);
                }

                if (isUnusedConfig || isObjValue) {
                    me[cfg.names.set](value);
                }
            }
        }
    },

    onDataChanged: function(store) {
        this.updateSprites();
        this.scheduleLayout();
    },

    onDataUpdate: function(store, record) {
        var me = this,
            sprites = me.sprites,
            ln = sprites.length,
            i = 0,
            sprite, spriteRecord, match;

        for (; i < ln; i++) {
            sprite = sprites[i];
            spriteRecord = sprite.getRecord();

            if (spriteRecord === record) {
                match = sprite;
                break;
            }
        }

        if (match) {
            me.updateSprite(match, record);
            me.scheduleLayout();
        }
    },

    toggleItem: function(sprite) {
        var disabledCount = 0,
            canToggle = true,
            store, i, count, record, disabled;

        if (!this.getToggleable() || !sprite.isLegendItem) {
            return;
        }

        store = this.getStore();

        if (store) {
            count = store.getCount();

            for (i = 0; i < count; i++) {
                record = store.getAt(i);

                if (record.get('disabled')) {
                    disabledCount++;
                }
            }

            canToggle = count - disabledCount > 1;

            record = sprite.getRecord();

            if (record) {
                disabled = record.get('disabled');

                if (disabled || canToggle) {
                    // This will trigger AbstractChart.onLegendStoreUpdate.
                    record.set('disabled', !disabled);
                    sprite.setAttributes({
                        enabled: disabled
                    });
                }
            }
        }
    },

    destroy: function() {
        var me = this;

        me.destroying = true;
        me.cancelLayout();
        me.setChart(null);

        me.callParent();
    }
});
