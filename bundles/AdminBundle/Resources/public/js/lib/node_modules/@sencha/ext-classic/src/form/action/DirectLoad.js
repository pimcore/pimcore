/**
 * Provides Ext Direct support for loading form data.
 *
 * This example illustrates usage of Ext Direct to load a form.
 *
 *     var myFormPanel = new Ext.form.Panel({
 *         // configs for FormPanel
 *         title: 'Basic Information',
 *         renderTo: document.body,
 *         width: 300, height: 160,
 *         padding: 10,
 *
 *         // configs apply to child items
 *         defaults: {anchor: '100%'},
 *         defaultType: 'textfield',
 *         items: [{
 *             fieldLabel: 'Name',
 *             name: 'name'
 *         },{
 *             fieldLabel: 'Email',
 *             name: 'email'
 *         },{
 *             fieldLabel: 'Company',
 *             name: 'company'
 *         }],
 *
 *         // configs for BasicForm
 *         api: {
 *             // The server-side method to call for load() requests
 *             load: 'Profile.getBasicInfo',
 *             // The server-side must mark the submit handler as a 'formHandler'
 *             submit: 'Profile.updateBasicInfo'
 *         },
 *         // specify the order for the passed params
 *         paramOrder: ['uid', 'foo']
 *     });
 *
 *     // load the form
 *     myFormPanel.getForm().load({
 *         // pass 2 arguments to server side getBasicInfo method (len=2)
 *         params: {
 *             foo: 'bar',
 *             uid: 34
 *         }
 *     });
 *
 * Before using DirectLoad action, make sure you set up Ext Direct remoting provider.
 * See {@link Ext.direct.Manager} for more information.
 *
 * For corresponding submit action, see {@link Ext.form.action.DirectSubmit}.
 */
Ext.define('Ext.form.action.DirectLoad', {
    extend: 'Ext.form.action.Load',
    alternateClassName: 'Ext.form.Action.DirectLoad',
    alias: 'formaction.directload',

    requires: [
        'Ext.direct.Manager'
    ],

    mixins: [
        'Ext.form.action.DirectAction'
    ],

    type: 'directload',

    run: function() {
        var me = this,
            form = me.form,
            metadata = me.metadata || form.metadata,
            timeout = me.timeout || form.timeout,
            args, fn;

        fn = me.resolveMethod('load');

        args = fn.directCfg.method.getArgs({
            params: me.getParams(),
            paramOrder: form.paramOrder,
            paramsAsHash: form.paramsAsHash,
            options: timeout != null ? { timeout: timeout * 1000 } : null,
            metadata: metadata,
            callback: me.onComplete,
            scope: me
        });

        fn.apply(window, args);
    },

    // Direct actions have already been processed and therefore
    // we can directly set the result; Direct Actions do not have
    // a this.response property.
    processResponse: function(result) {
        return (this.result = result);
    },

    onComplete: function(data) {
        if (data) {
            this.onSuccess(data);
        }
        else {
            this.onFailure(null);
        }
    }
});
