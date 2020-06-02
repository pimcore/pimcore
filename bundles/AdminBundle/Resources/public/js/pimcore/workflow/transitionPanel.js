/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.workflow.transitionPanel");
pimcore.workflow.transitionPanel = Class.create({

    getClassName: function ()
    {
        return "pimcore.workflow.transitionPanel";
    },

    initialize: function(elementType, elementId, elementEditor, workflowName, transitionConfig)
    {
        this.elementType = elementType;
        this.elementId = elementId;
        this.elementEditor = elementEditor;
        this.workflowName = workflowName;
        this.transitionConfig = transitionConfig;

        //build the window and in turn this will create the form panels etc
        this.getTransitionWindow();

        //show the action window
        this.transitionWindow.show();

    },


    getFormPanelItems: function()
    {
        var items = [{
            xtype: 'container',
            itemId: 'workflowMessage',
            html: ''
        },{
            xtype: 'hiddenfield',
            name: 'cid',
            value: this.elementId
        },{
            xtype: 'hiddenfield',
            name: 'ctype',
            value: this.elementType
        },{
            xtype: 'fieldset',
            title: t('workflow_additional_info'),
            itemId: 'additionalInfoFieldset',
            defaults: {
                labelWidth: 200
            },
            items: [],
            hidden: true
        }];

        if(this.transitionConfig.notes.commentEnabled) {

            var height = this.transitionConfig.notes.additionalFields.length > 0 ? 200 : 300;

            items.push({
                xtype: 'fieldset',
                itemId: 'notesFieldset',
                defaults: {
                    labelWidth: 200
                },
                title: t('workflow_notes'),
                items: [{
                    width: 450,
                    height: height,
                    xtype: 'textareafield',
                    itemId: 'notesField',
                    name: 'workflow[notes]',
                    value: this.transitionConfig.notes.commentPrefill,
                    allowBlank: !this.transitionConfig.notes.commentRequired
                }]
            });
        }

        return items;
    },

    getWorkflowFormPanel: function()
    {

        if(!this.workflowFormPanel) {

            //initialise the formpanel as it doesn't exist
            this.workflowFormPanel = new Ext.form.FormPanel({
                border: false,
                frame:false,
                bodyStyle: 'padding:10px',
                items: this.getFormPanelItems(),
                defaults: {
                    labelWidth: 200
                },
                collapsible: false,
                autoScroll: true
            });


            this.setAdditionalFields(this.transitionConfig.notes.additionalFields);
        }

        return this.workflowFormPanel
    },


    /**
     * Return the value of the transitionPanel form
     * @returns object
     */
    getWorkflowFormPanelValues: function()
    {

        var values = this.getWorkflowFormPanel().getValues();

        values.workflowName = this.workflowName;
        values.transition = this.transitionConfig.name;

        if (this.additionalFields) {

            Ext.each(this.additionalFields, function(cf) {

                try {
                    values[cf.getName()] = cf.getValue();
                } catch(e) {
                    values[cf.getName()] = '';
                }

            }, this);

        }

        return values;

    },



    getTransitionWindow: function()
    {

        if (!this.transitionWindow) {
            var height = 510;
            this.transitionWindow = new Ext.Window({
                width: 530,
                height: height,
                iconCls: this.transitionConfig.iconCls,
                title: this.transitionConfig.label,
                layout: "fit",
                closeAction:'close',
                plain: true,
                maximized: false,
                autoScroll: true,
                modal: true,
                buttons: [
                    {
                        text: t('cancel'),
                        iconCls: "pimcore_icon_empty",
                        handler: function(){
                            this.transitionWindow.hide();
                            this.transitionWindow.destroy();
                        }.bind(this)
                    },{
                        text: t('workflow_perform_action'),
                        itemId: 'performActionButton',
                        iconCls: "pimcore_icon_workflow_action",
                        handler: this.submitWorkflowTransition.bind(this)
                    }
                ]
            });

            //now initialise the form panel in this window
            var formPanel = this.getWorkflowFormPanel();
            this.transitionWindow.add(formPanel);
        }

        return this.transitionWindow;
    },

    genericError: function()
    {
        this._isLoading = false;
        console.log(arguments);
    },


    /**
     * Quick accessor for the Notes Field
     * @returns {*|Ext.Component}
     */
    getNotesField: function()
    {
        return this.getWorkflowFormPanel().getComponent('notesFieldset').getComponent('notesField');
    },



    /**
     * As the only way to get the submit button is messy, I've created this for it.
     * TODO, find a better way of achieving getting the button
     * Note that non of the regularly documented methods actually work :-(
     */
    getSubmitButton: function()
    {
        return this.getTransitionWindow().getDockedItems()[1].getComponent('performActionButton');
    },


    /**
     * Adds a number of additional fields to the additional fields fieldset
     * @param additional
     */
    setAdditionalFields: function(additional)
    {
        var additionalFieldset = this.getWorkflowFormPanel().getComponent('additionalInfoFieldset');

        //remove all existing fields from the fieldsset first
        additionalFieldset.removeAll();
        this.additionalFields = [];

        Ext.each(additional, function(a) {
            //add a new field
            var field = {};
            var supportedTags = ['input', 'textarea', 'select', 'datetime', 'date', 'user', 'checkbox'];

            var c = a.fieldTypeSettings;
            c.name = 'workflow[additional][' + a.name + ']';
            c.fieldType = a.fieldType;
            c.title = a.title;
            c.labelWidth = c.labelWidth ? c.labelWidth : 200;


            if (in_array(c.fieldType, supportedTags)) {

                try {
                    //create new pimcore tag field
                    var tag = new pimcore.object.tags[c.fieldType](null, c);
                    this.additionalFields.push(tag);

                    //width fix
                    field = tag.getLayoutEdit();
                    field.setWidth(450);

                    if (c.fieldType === 'textarea') {
                        field.setHeight(100);
                    }

                } catch(e) {
                    console.error('Could not add additional field');
                    console.info(e);
                }

            }

            additionalFieldset.add(field);
        }, this);

        if(additionalFieldset.items.length) {
            additionalFieldset.show();
        } else {
            additionalFieldset.hide();
        }

    },

    /**
     * Performs the action selected in the Action Form
     *
     */
    submitWorkflowTransition: function()
    {
        if(!this.getNotesField().validate()) {
            return; //ui will handle the error
        }

        this.getSubmitButton().setDisabled(true);


        var formvars = this.getWorkflowFormPanelValues();

        //send a request to the server with the current form data
        Ext.Ajax.request({
            url : this.transitionConfig.isGlobalAction ? Routing.generate('pimcore_admin_workflow_submitglobal') : Routing.generate('pimcore_admin_workflow_submitworkflowtransition'),
            method: 'post',
            params: formvars,
            success: this.onSubmitWorkflowTransitionResponse.bind(this),
            failure: this.genericError.bind(this)
        });


    },

    onSubmitWorkflowTransitionResponse: function(response)
    {
        var data = Ext.decode(response.responseText);

        if (data.success) {

            this.transitionWindow.hide();
            this.transitionWindow.destroy();

            if (data.callback && typeof this[data.callback] === 'function') {
                this[data.callback].call(this);
            }

        } else {
            this.getWorkflowFormPanel().getComponent('workflowMessage').setHtml(
                [
                    '<div class="action_error">' + data.message + '</div>',
                    '<div class="action_reason">' + data.reason + '</div>'
                ].join(''));

            this.getWorkflowFormPanel().scrollTo(0, 0);

            this.getSubmitButton().setDisabled(false);
        }

    },

    reloadObject: function() {
        this.elementEditor.reload({layoutId: this.transitionConfig.objectLayout});
    }
});
