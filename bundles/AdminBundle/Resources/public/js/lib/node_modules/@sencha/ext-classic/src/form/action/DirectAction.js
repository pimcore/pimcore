/**
 * @class Ext.form.action.DirectAction
 * A mixin that contains methods specific to Ext Direct actions shared
 * by DirectLoad and DirectSubmit.
 * @private
 */
Ext.define('Ext.form.action.DirectAction', {
    extend: 'Ext.Mixin',

    mixinConfig: {
        id: 'directaction'
    },

    resolveMethod: function(type) {
        var me = this,
            form = me.form,
            api, fn;

        api = Ext.direct.Manager.resolveApi(form.api, me);

        //<debug>
        if (!api) {
            Ext.raise("Cannot resolve Ext Direct API method for " + type +
                            " action; form " + form.id + " has no api object defined");
        }
        //</debug>

        fn = api[type];

        if (!fn) {
            Ext.raise("Cannot resolve Ext Direct API method for " + type + " action");
        }

        return fn;
    }
});
