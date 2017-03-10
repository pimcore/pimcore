/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

pimcore.registerNS("pimcore.workflowmanagement.actionPanel");
pimcore.workflowmanagement.actionPanel = Class.create({

    getClassName: function ()
    {
        return "pimcore.workflowmanagement.actionPanel";
    },

    initialize: function(elementType, elementId, elementEditor)
    {
        this.elementType = elementType;
        this.elementId = elementId;
        this.elementEditor = elementEditor;

        this.initStores();
 
        //build the window and in turn this will create the form panels etc
        this.getActionWindow();

        //show the action window
        this.actionWindow.show();

        //now refresh the form panel to build the correct items
        this.requestWorkflowFormConfig();

    },

    initStores: function()
    {

        this.actionStore = Ext.create('Ext.data.JsonStore', {
            storeId: 'action_store',
            fields: ['value','label'],
            data: []
        });

        this.stateStore = Ext.create('Ext.data.JsonStore', {
            storeId: 'state_store',
            fields: ['value','label'],
            data: []
        });

        this.statusStore = Ext.create('Ext.data.JsonStore', {
            storeId: 'status_store',
            fields: ['value','label'],
            data: []
        });

    },


    getInitialFormPanelItems: function()
    {
        return [{
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
            itemId: 'actionFieldset',
            title: t('workflow_action_detail'),
            defaults: {
                xtype: 'combo',
                disabled: true,
                queryMode: 'local',
                valueField: 'value',
                displayField: 'label',
                triggerAction: 'all',
                forceSelection: true,
                labelWidth: 200,
                width: 450
            },
            items: [{
                fieldLabel: t('workflow_select_action'),
                itemId: 'actionField',
                name: 'workflow[action]',
                store: this.actionStore,
                listeners: {
                    change: this.requestWorkflowFormConfig.bind(this)
                }
              },{
                fieldLabel: t('workflow_select_new_state'),
                itemId: 'stateField',
                name: 'workflow[newState]',
                store: this.stateStore,
                listeners: {
                    change: this.requestWorkflowFormConfig.bind(this)
                }
              },{
                fieldLabel: t('workflow_select_new_status'),
                itemId: 'statusField',
                name: 'workflow[newStatus]',
                store: this.statusStore,
                listeners: {
                    change: this.requestWorkflowFormConfig.bind(this)
                }
              }]

        },{
            xtype: 'fieldset',
            title: t('workflow_additional_info'),
            itemId: 'additionalInfoFieldset',
            defaults: {
                labelWidth: 200
            },
            items: [],
            hidden: true
        },{
            xtype: 'fieldset',
            itemId: 'notesFieldset',
            defaults: {
                labelWidth: 200
            },
            title: t('workflow_notes'),
            items: [{
                width: 450,
                xtype: 'textareafield',
                itemId: 'notesField',
                name: 'workflow[notes]',
                value: '',
                allowBlank: true
            }]
        }];
    },

    getWorkflowFormPanel: function()
    {

        if(!this.workflowFormPanel) {

            //initialise the formpanel as it doesn't exist
            this.workflowFormPanel = new Ext.form.FormPanel({
                border: false,
                frame:false,
                bodyStyle: 'padding:10px',
                items: this.getInitialFormPanelItems(),
                defaults: {
                    labelWidth: 200
                },
                collapsible: false,
                autoScroll: true
            });

        }

        return this.workflowFormPanel
    },


    /**
     * Return the value of the actionpanel form
     * @returns object
     */
    getWorkflowFormPanelValues: function()
    {

        var values = this.getWorkflowFormPanel().getValues();

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



    getActionWindow: function()
    {

        if (!this.actionWindow) {
            var height = 510;
            this.actionWindow = new Ext.Window({
                width: 530,
                height: height,
                iconCls: "pimcore_icon_workflow_action",
                title: this.elementEditor.data.workflowManagement.workflowName + ' ' + t('workflow_actions'),
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
                            this.actionWindow.hide();
                            this.actionWindow.destroy();
                        }.bind(this)
                    },{
                        text: t('workflow_perform_action'),
                        itemId: 'performActionButton',
                        iconCls: "pimcore_icon_workflow_action",
                        handler: this.submitWorkflowTransition.bind(this),
                        disabled: true
                    }
                ]
            });

            //now initialise the form panel in this window
            var formPanel = this.getWorkflowFormPanel();
            this.actionWindow.add(formPanel);
        }

        return this.actionWindow;
    },

    /**
     * Loads the form configuration for the current action window
     */
    requestWorkflowFormConfig: function()
    {
        if (this._isLoading) {
            return;
        }
        this._isLoading = true;

        //send a request to the server with the current form data
        Ext.Ajax.request({
            url : '/admin/workflow/get-workflow-form',
            method: 'post',
            params: this.getWorkflowFormPanel().getValues(),
            success: this.refreshWorkflowFormPanelItems.bind(this),
            failure: this.genericError.bind(this)
        });
    },


    refreshWorkflowFormPanelItems: function(response)
    {
        var data = Ext.decode(response.responseText);

        this.setAvailableActions(data.available_actions);
        this.setAvailableStates(data.available_states);
        this.setAvailableStatuses(data.available_statuses);
        this.setAdditionalFields(data.additional_fields);
        this.setNotesRequired(data.notes_required);

        //set this here so that state/status/action selection changes above don't trigger further admin requests
        this._isLoading = false;

        //set the messagge
        this.getWorkflowFormPanel().getComponent('workflowMessage').setHtml(data.message);

    },

    genericError: function()
    {
        this._isLoading = false;
        console.log(arguments);
    },


    /**
     * Sets the available actions within the form
     */
    setAvailableActions: function(actions)
    {

        var actionField = this.getWorkflowFormPanel().getComponent('actionFieldset').getComponent('actionField');
        actionField.setDisabled(false);

        this.actionStore.loadData(actions, false);

        if (!actions) {
            actionField.setDisabled(true);
        } else if(actions.length === 1) {
            actionField.setValue(actions[0].value);
            actionField.setDisabled(true);
        }

    },

    /**
     * Sets the available actions within the form
     */
    setAvailableStates: function(states)
    {

        var stateField = this.getWorkflowFormPanel().getComponent('actionFieldset').getComponent('stateField');
        stateField.setDisabled(false);

        this.stateStore.loadData(states, false);

        if (!this.stateStore.getCount()) {
            stateField.setDisabled(true);
        } else if (this.stateStore.getCount() === 1) {
            stateField.setValue(states[0].value);
            stateField.setDisabled(true);
        } else {
            //check that the current state value is valid, if not then remove it
            var currentValue = stateField.getValue();
            if (this.stateStore.find('value', currentValue, 0, false, true, true) === -1) {
                stateField.setValue(null);
            }
        }

    },

    /**
     * Sets the available actions within the form
     */
    setAvailableStatuses: function(statuses)
    {

        var statusField = this.getWorkflowFormPanel().getComponent('actionFieldset').getComponent('statusField');
        statusField.setDisabled(false);

        this.statusStore.loadData(statuses, false);

        if (!this.statusStore.getCount()) {
            statusField.setDisabled(true);
        } else if (this.statusStore.getCount() === 1) {
            statusField.setValue(statuses[0].value);
            statusField.setDisabled(true);
        } else {
            //check that the current status value is valid, if not then remove it
            var currentValue = statusField.getValue();
            if (this.statusStore.find('value', currentValue, 0, false, true, true) === -1) {
                statusField.setValue(null);
            }
        }

        // window.actionWindow = this.getActionWindow();

        if (statusField.getValue()) {
            this.getSubmitButton().setDisabled(false);
        } else {
            this.getSubmitButton().setDisabled(true);
        }


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
     * Sets the status of the notes field depending on the action
     */
    setNotesRequired: function(required)
    {
        var notesField = this.getNotesField();
        notesField.allowBlank = !required;
        notesField.setValue(null);

        //set the label of the fieldset
        if(required) {
            notesField.findParentByType('fieldset').setTitle(t('workflow_notes_required'));
        } else {
            notesField.findParentByType('fieldset').setTitle(t('workflow_notes_optional'));
        }

    },


    /**
     * As the only way to get the submit button is messy, I've created this for it.
     * TODO, find a better way of achieving getting the button
     * Note that non of the regularly documented methods actually work :-(
     */
    getSubmitButton: function()
    {
        return this.getActionWindow().getDockedItems()[1].getComponent('performActionButton');
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

        Ext.each(additional, function(c) {
            //add a new field
            var field = {};
            var supportedTags = ['input', 'textarea', 'select', 'datetime', 'date', 'user'];

            if (in_array(c.fieldType, supportedTags)) {

                try {

                    //namespace the workflow form
                    c.name = 'workflow[additional][' + c.name + ']';
                    c.labelWidth = 200;

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

        //temporarily enable all fields
        var fieldset = this.getWorkflowFormPanel().getComponent('actionFieldset');
        fieldset.getComponent('actionField').setDisabled(false);
        fieldset.getComponent('stateField').setDisabled(false);
        fieldset.getComponent('statusField').setDisabled(false);

        var formvars = this.getWorkflowFormPanelValues();

        //lock the values
        fieldset.getComponent('actionField').setDisabled(true);
        fieldset.getComponent('stateField').setDisabled(true);
        fieldset.getComponent('statusField').setDisabled(true);

        //send a request to the server with the current form data
        Ext.Ajax.request({
            url : '/admin/workflow/submit-workflow-transition',
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

            this.actionWindow.hide();
            this.actionWindow.destroy();

            if (data.callback && typeof this[data.callback] === 'function') {
                this[data.callback].call(this);
            }

        } else {
            this.getWorkflowFormPanel().getComponent('workflowMessage').setHtml(
                [
                    '<div class="action_error">' + data.message + '</div>',
                    '<div class="action_reason">' + data.reason + '</div>'
                ].join(''));
        }

    },

    reloadObject: function() {
        this.elementEditor.reload();
    }
    

});
