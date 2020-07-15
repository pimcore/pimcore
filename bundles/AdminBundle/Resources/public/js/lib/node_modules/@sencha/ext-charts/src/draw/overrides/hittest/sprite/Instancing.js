/**
 * @private
 * Adds hit testing methods to the Ext.draw.sprite.Instancing.
 * Included by the Ext.draw.plugin.SpriteEvents.
 */
Ext.define('Ext.draw.overrides.hittest.sprite.Instancing', {
    override: 'Ext.draw.sprite.Instancing',

    /**
     * Performs a hit test on the instances of an instancing sprite.
     * @param point A two-item array containing x and y coordinates of the point.
     * @param options Hit testing options.
     * @return {Object} A hit result object that contains more information about what
     * exactly was hit or null if nothing was hit.
     * @return {Boolean} return.isInstance `true` if an instance was hit.
     * @return {Ext.draw.sprite.Instancing} return.sprite The instancing sprite.
     * @return {Ext.draw.sprite.Sprite} return.template The template of the instancing sprite.
     * @return {Object} return.instance The attributes of the instance.
     * @return {Number} return.index The index of the instance.
     */
    hitTest: function(point, options) {
        var me = this,
            template = me.getTemplate(),
            originalAttr = template.attr,
            instances = me.instances,
            ln = instances.length,
            i = 0,
            result = null;

        if (!me.isVisible()) {
            return result;
        }

        for (; i < ln; i++) {
            template.attr = instances[i];
            result = template.hitTest(point, options);

            if (result) {
                result.isInstance = true;
                result.template = result.sprite;
                result.sprite = this;
                result.instance = instances[i];
                result.index = i;

                return result;
            }
        }

        template.attr = originalAttr;

        return result;
    }

});
