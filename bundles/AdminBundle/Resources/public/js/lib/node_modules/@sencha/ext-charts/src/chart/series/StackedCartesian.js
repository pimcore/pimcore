/**
 * @abstract
 * @extends Ext.chart.series.Cartesian
 * Abstract class for all the stacked cartesian series including area series
 * and bar series.
 */
Ext.define('Ext.chart.series.StackedCartesian', {

    extend: 'Ext.chart.series.Cartesian',

    config: {
        /**
         * @cfg {Boolean} [stacked=true]
         * `true` to display the series in its stacked configuration.
         */
        stacked: true,

        /**
         * @cfg {Boolean} [splitStacks=true]
         * `true` to stack negative/positive values in respective y-axis directions.
         */
        splitStacks: true,

        /**
         * @cfg {Boolean} [fullStack=false]
         * If `true`, the height of a stacked bar is always the full height of the chart,
         * with individual components viewed as shares of the whole determined by the
         * {@link #fullStackTotal} config.
         */
        fullStack: false,

        /**
         * @cfg {Boolean} [fullStackTotal=100]
         * If the {@link #fullStack} config is set to `true`, this will determine
         * the absolute total value of each stack.
         */
        fullStackTotal: 100,

        /**
         * @cfg {Array} hidden
         */
        hidden: []
    },

    /**
     * @private
     * @property
     * If `true`, each subsequent sprite has a lower zIndex so that the stroke of previous
     * sprite in the stack is not covered by the next sprite (which makes the very top
     * segment look odd in flat bar and area series, especially when wide strokes are used).
     */
    reversedSpriteZOrder: true,

    spriteAnimationCount: 0,

    themeColorCount: function() {
        var me = this,
            yField = me.getYField();

        return Ext.isArray(yField) ? yField.length : 1;
    },

    updateStacked: function() {
        this.processData();
    },

    updateSplitStacks: function() {
        this.processData();
    },

    coordinateY: function() {
        return this.coordinateStacked('Y', 1, 2);
    },

    coordinateStacked: function(direction, directionOffset, directionCount) {
        var me = this,
            store = me.getStore(),
            items = store.getData().items,
            itemCount = items.length,
            axis = me['get' + direction + 'Axis'](),
            hidden = me.getHidden(),
            splitStacks = me.getSplitStacks(),
            fullStack = me.getFullStack(),
            fullStackTotal = me.getFullStackTotal(),
            range = [0, 0],
            directions = me['fieldCategory' + direction],
            dataStart = [],
            posDataStart = [],
            negDataStart = [],
            dataEnd,
            stacked = me.getStacked(),
            sprites = me.getSprites(),
            coordinatedData = [],
            i, j, k, fields, fieldCount,
            posTotals, negTotals,
            fieldCategoriesItem,
            data, attr;

        if (!sprites.length) {
            return;
        }

        for (i = 0; i < directions.length; i++) {

            fieldCategoriesItem = directions[i];
            fields = me.getFields([fieldCategoriesItem]);
            fieldCount = fields.length;

            for (j = 0; j < itemCount; j++) {
                dataStart[j] = 0;
                posDataStart[j] = 0;
                negDataStart[j] = 0;
            }

            for (j = 0; j < fieldCount; j++) {
                if (!hidden[j]) {
                    coordinatedData[j] = me.coordinateData(items, fields[j], axis);
                }
            }

            if (stacked && fullStack) {
                posTotals = [];

                if (splitStacks) {
                    negTotals = [];
                }

                for (j = 0; j < itemCount; j++) {
                    posTotals[j] = 0;

                    if (splitStacks) {
                        negTotals[j] = 0;
                    }

                    for (k = 0; k < fieldCount; k++) {
                        data = coordinatedData[k];

                        if (!data) {
                            // If the field is hidden there's no coordinated data for it.
                            continue;
                        }

                        data = data[j];

                        if (data >= 0 || !splitStacks) {
                            posTotals[j] += data;
                        }
                        else if (data < 0) {
                            negTotals[j] += data;
                        } // else not a valid number
                    }
                }
            }

            for (j = 0; j < fieldCount; j++) {

                attr = {};

                if (hidden[j]) {
                    attr['dataStart' + fieldCategoriesItem] = dataStart;
                    attr['data' + fieldCategoriesItem] = dataStart;
                    sprites[j].setAttributes(attr);
                    continue;
                }

                data = coordinatedData[j];

                if (stacked) {

                    dataEnd = [];

                    for (k = 0; k < itemCount; k++) {
                        if (!data[k]) {
                            data[k] = 0;
                        }

                        if (data[k] >= 0 || !splitStacks) {
                            if (fullStack && posTotals[k]) {
                                data[k] *= fullStackTotal / posTotals[k];
                            }

                            dataStart[k] = posDataStart[k];
                            posDataStart[k] += data[k];
                            dataEnd[k] = posDataStart[k];
                        }
                        else {
                            if (fullStack && negTotals[k]) {
                                data[k] *= fullStackTotal / negTotals[k];
                            }

                            dataStart[k] = negDataStart[k];
                            negDataStart[k] += data[k];
                            dataEnd[k] = negDataStart[k];
                        }
                    }

                    attr['dataStart' + fieldCategoriesItem] = dataStart;
                    attr['data' + fieldCategoriesItem] = dataEnd;

                    Ext.chart.Util.expandRange(range, dataStart);
                    Ext.chart.Util.expandRange(range, dataEnd);

                }
                else {

                    attr['dataStart' + fieldCategoriesItem] = dataStart;
                    attr['data' + fieldCategoriesItem] = data;

                    Ext.chart.Util.expandRange(range, data);
                }

                sprites[j].setAttributes(attr);
            }
        }

        range = Ext.chart.Util.validateRange(range, me.defaultRange);

        me.dataRange[directionOffset] = range[0];
        me.dataRange[directionOffset + directionCount] = range[1];

        attr = {};
        attr['dataMin' + direction] = range[0];
        attr['dataMax' + direction] = range[1];

        for (i = 0; i < sprites.length; i++) {
            sprites[i].setAttributes(attr);
        }
    },

    getFields: function(fieldCategory) {
        var me = this,
            fields = [],
            ln = fieldCategory.length,
            i, fieldsItem;

        for (i = 0; i < ln; i++) {
            fieldsItem = me['get' + fieldCategory[i] + 'Field']();

            if (Ext.isArray(fieldsItem)) {
                fields.push.apply(fields, fieldsItem);
            }
            else {
                fields.push(fieldsItem);
            }
        }

        return fields;
    },

    updateLabelOverflowPadding: function(labelOverflowPadding) {
        var me = this,
            label;

        if (!me.isConfiguring) {
            label = me.getLabel();

            if (label) {
                label.setAttributes({ labelOverflowPadding: labelOverflowPadding });
            }
        }
    },

    updateLabelData: function() {
        var me = this,
            label = me.getLabel();

        if (label) {
            label.setAttributes({ labelOverflowPadding: me.getLabelOverflowPadding() });
        }

        me.callParent();
    },

    getSprites: function() {
        var me = this,
            chart = me.getChart(),
            fields = me.getFields(me.fieldCategoryY),
            itemInstancing = me.getItemInstancing(),
            sprites = me.sprites,
            hidden = me.getHidden(),
            spritesCreated = false,
            fieldCount = fields.length,
            i, sprite;

        if (!chart) {
            return [];
        }

        // Create one Ext.chart.series.sprite.StackedCartesian sprite per field.
        for (i = 0; i < fieldCount; i++) {
            sprite = sprites[i];

            if (!sprite) {
                sprite = me.createSprite();
                sprite.setAttributes({
                    zIndex: (me.reversedSpriteZOrder ? -1 : 1) * i
                });
                sprite.setField(fields[i]);
                spritesCreated = true;
                hidden.push(false);

                if (itemInstancing) {
                    sprite.getMarker('items').getTemplate().setAttributes(me.getStyleByIndex(i));
                }
                else {
                    sprite.setAttributes(me.getStyleByIndex(i));
                }
            }
        }

        if (spritesCreated) {
            me.updateHidden(hidden);
        }

        return sprites;
    },

    getItemForPoint: function(x, y) {
        var me = this,
            sprites = me.getSprites(),
            store = me.getStore(),
            hidden = me.getHidden(),
            minDistance = Infinity,
            item = null,
            spriteIndex = -1,
            pointIndex = -1,
            point,
            yField,
            sprite,
            i, ln;

        for (i = 0, ln = sprites.length; i < ln; i++) {
            if (hidden[i]) {
                continue;
            }

            sprite = sprites[i];
            point = sprite.getNearestDataPoint(x, y);

            // Don't stop when the first matching point is found.
            // Keep looking for the nearest point.
            if (point) {
                if (point.distance < minDistance) {
                    minDistance = point.distance;
                    pointIndex = point.index;
                    spriteIndex = i;
                }
            }
        }

        if (spriteIndex > -1) {
            yField = me.getYField();
            item = {
                series: me,
                sprite: sprites[spriteIndex],
                category: me.getItemInstancing() ? 'items' : 'markers',
                index: pointIndex,
                record: store.getData().items[pointIndex],
                // Handle the case where we're stacked but a single segment
                field: typeof yField === 'string' ? yField : yField[spriteIndex],
                distance: minDistance
            };
        }

        return item;
    },

    provideLegendInfo: function(target) {
        var me = this,
            sprites = me.getSprites(),
            title = me.getTitle(),
            field = me.getYField(),
            hidden = me.getHidden(),
            single = sprites.length === 1,
            style, fill,
            i, name;

        for (i = 0; i < sprites.length; i++) {
            style = me.getStyleByIndex(i);
            fill = style.fillStyle;

            if (title) {
                if (Ext.isArray(title)) {
                    name = title[i];
                }
                else if (single) {
                    name = title;
                }
            }

            if (!title || !name) {
                if (Ext.isArray(field)) {
                    name = field[i];
                }
                else {
                    name = me.getId();
                }
            }

            target.push({
                name: name,
                mark: (Ext.isObject(fill) ? fill.stops && fill.stops[0].color : fill) ||
                       style.strokeStyle || 'black',
                disabled: hidden[i],
                series: me.getId(),
                index: i
            });
        }
    },

    onSpriteAnimationStart: function(sprite) {
        this.spriteAnimationCount++;

        if (this.spriteAnimationCount === 1) {
            this.fireEvent('animationstart');
        }
    },

    onSpriteAnimationEnd: function(sprite) {
        this.spriteAnimationCount--;

        if (this.spriteAnimationCount === 0) {
            this.fireEvent('animationend');
        }
    }
});
