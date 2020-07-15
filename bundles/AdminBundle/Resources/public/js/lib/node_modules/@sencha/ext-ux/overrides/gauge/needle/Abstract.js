Ext.define(null, {
    override: 'Ext.ux.gauge.needle.Abstract',

    compatibility: Ext.isIE10p,

    setTransform: function(centerX, centerY, rotation) {
        var needleGroup = this.getNeedleGroup();

        this.callParent([centerX, centerY, rotation]);

        needleGroup.set({
            transform: getComputedStyle(needleGroup.dom).getPropertyValue('transform')
        });
    },

    updateStyle: function(style) {
        var pathElement;

        this.callParent([style]);

        if (Ext.isObject(style) && 'transform' in style) {
            pathElement = this.getNeedlePath();

            pathElement.set({
                transform: getComputedStyle(pathElement.dom).getPropertyValue('transform')
            });
        }
    }
});
