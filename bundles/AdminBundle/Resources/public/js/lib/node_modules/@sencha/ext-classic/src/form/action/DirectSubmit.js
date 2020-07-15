/**
 * Provides Ext Direct support for submitting form data.
 *
 * This example illustrates usage of Ext Direct to submit a form.
 *
 *     var myFormPanel = new Ext.form.Panel({
 *         // configs for FormPanel
 *         title: 'Basic Information',
 *         renderTo: document.body,
 *         width: 300, height: 160,
 *         padding: 10,
 *         buttons:[{
 *             text: 'Submit',
 *             handler: function(){
 *                 myFormPanel.getForm().submit({
 *                     params: {
 *                         foo: 'bar',
 *                         uid: 34
 *                     }
 *                 });
 *             }
 *         }],
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
 * Before using DirectLoad action, make sure you set up Ext Direct remoting provider.
 * See {@link Ext.direct.Manager} for more information.
 *
 * For corresponding load action, see {@link Ext.form.action.DirectLoad}.
 */
Ext.define('Ext.form.action.DirectSubmit', {
    extend: 'Ext.form.action.Submit',
    alternateClassName: 'Ext.form.Action.DirectSubmit',
    alias: 'formaction.directsubmit',

    requires: [
        'Ext.direct.Manager'
    ],

    mixins: [
        'Ext.form.action.DirectAction'
    ],

    type: 'directsubmit',

    doSubmit: function() {
        var me = this,
            form = me.form,
            metadata = me.metadata || form.metadata,
            timeout = me.timeout || form.timeout,
            fn, formInfo, args;

        fn = me.resolveMethod('submit');
        formInfo = me.buildForm();

        args = fn.directCfg.method.getArgs({
            params: formInfo.formEl,
            options: timeout != null ? { timeout: timeout * 1000 } : null,
            metadata: metadata,
            callback: me.onComplete,
            scope: me
        });

        fn.apply(window, args);

        me.cleanup(formInfo);
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
