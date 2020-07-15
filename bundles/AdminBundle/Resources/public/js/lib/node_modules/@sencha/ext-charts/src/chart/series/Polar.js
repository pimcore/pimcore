/**
 * @abstract
 * @class Ext.chart.series.Polar
 * @extends Ext.chart.series.Series
 *
 * Common base class for series implementations that plot values using polar coordinates.
 *
 * Polar charts accept angles in radians. You can calculate radians with the following
 * formula:
 *
 *      radians = degrees x Π/180
 */
Ext.define('Ext.chart.series.Polar', {

    extend: 'Ext.chart.series.Series',

    config: {

        /**
         * @cfg {Number} [rotation=0]
         * The angle in radians at which the first polar series item should start.
         */
        rotation: 0,

        /**
         * @cfg {Number} radius
         * @private
         * Use {@link Ext.chart.series.Pie#cfg!radiusFactor radiusFactor} instead.
         *
         * The internally used radius of the polar series. Set to `null` will fit the
         * polar series to the boundary.
         */
        radius: null,

        /**
         * @cfg {Array} center for the polar series.
         */
        center: [0, 0],

        /**
         * @cfg {Number} [offsetX=0]
         * The x-offset of center of the polar series related to the center of the boundary.
         */
        offsetX: 0,

        /**
         * @cfg {Number} [offsetY=0]
         * The y-offset of center of the polar series related to the center of the boundary.
         */
        offsetY: 0,

        /**
         * @cfg {Boolean} [showInLegend=true]
         * Whether to add the series elements as legend items.
         */
        showInLegend: true,

        /**
         * @private
         * @cfg {String} xField
         */
        xField: null,

        /**
         * @private
         * @cfg {String} yField
         */
        yField: null,

        /**
         * @cfg {String} angleField
         * The store record field name for the angular axes in radar charts,
         * or the size of the slices in pie charts.
         */
        angleField: null,

        /**
         * @cfg {String} radiusField
         * The store record field name for the radial axes in radar charts,
         * or the radius of the slices in pie charts.
         */
        radiusField: null,

        xAxis: null,

        yAxis: null
    },

    directions: ['X', 'Y'],
    fieldCategoryX: ['X'],
    fieldCategoryY: ['Y'],

    deprecatedConfigs: {
        field: 'angleField',
        lengthField: 'radiusField'
    },

    constructor: function(config) {
        var me = this,
            configurator = me.self.getConfigurator(),
            configs = configurator.configs,
            p;

        if (config) {
            for (p in me.deprecatedConfigs) {
                if (p in config && !(config in configs)) {
                    Ext.raise("'" + p + "' config has been deprecated. Please use the '" +
                        me.deprecatedConfigs[p] + "' config instead.");
                }
            }
        }

        me.callParent([config]);
    },

    getXField: function() {
        return this.getAngleField();
    },

    updateXField: function(value) {
        this.setAngleField(value);
    },

    getYField: function() {
        return this.getRadiusField();
    },

    updateYField: function(value) {
        this.setRadiusField(value);
    },

    applyXAxis: function(newAxis, oldAxis) {
        return this.getChart().getAxis(newAxis) || oldAxis;
    },

    applyYAxis: function(newAxis, oldAxis) {
        return this.getChart().getAxis(newAxis) || oldAxis;
    },

    getXRange: function() {
        return [this.dataRange[0], this.dataRange[2]];
    },

    getYRange: function() {
        return [this.dataRange[1], this.dataRange[3]];
    },

    themeColorCount: function() {
        var me = this,
            store = me.getStore(),
            count = store && store.getCount() || 0;

        return count;
    },

    isStoreDependantColorCount: true,

    getDefaultSpriteConfig: function() {
        return {
            type: this.seriesType,
            renderer: this.getRenderer(),
            centerX: 0,
            centerY: 0,
            rotationCenterX: 0,
            rotationCenterY: 0
        };
    },

    applyRotation: function(rotation) {
        return Ext.draw.sprite.AttributeParser.angle(Ext.draw.Draw.rad(rotation));
    },

    updateRotation: function(rotation) {
        var sprites = this.getSprites();

        if (sprites && sprites[0]) {
            sprites[0].setAttributes({
                baseRotation: rotation
            });
        }
    }
});
