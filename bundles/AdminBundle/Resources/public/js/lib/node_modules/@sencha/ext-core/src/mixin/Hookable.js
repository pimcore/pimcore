/**
 * @private
 */
Ext.define('Ext.mixin.Hookable', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'hookable'
    },

    bindHook: function(instance, boundMethod, bindingMethod, preventDefault, extraArgs) {
        instance.afterMethod(boundMethod, bindingMethod || boundMethod, this, preventDefault,
                             extraArgs);

        return this;
    },

    unbindHook: function(instance, boundMethod, bindingMethod) {
        instance.removeMethodListener(boundMethod, bindingMethod || boundMethod, this);

        return this;
    }
});
