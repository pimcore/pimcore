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

pimcore.registerNS("pimcore.settings.workflow.item");
pimcore.settings.workflow.item = Class.create({

    initialize: function (id, parentPanel) {
        this.parentPanel = parentPanel;
        this.id = id;

        Ext.Ajax.request({
            url: "/admin/workflow-settings/get",
            success: this.loadComplete.bind(this),
            params: {
                id: this.id
            }
        });
    },

    loadComplete: function (transport) {
        var response = Ext.decode(transport.responseText);
        if(response && response.success) {
            this.data = response.workflow;

            var modelName = 'PimcoreWorkflow';
            if(!Ext.ClassManager.isCreated(modelName) ) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    idProperty: 'name'
                });
            }

            this.statesStore = new Ext.data.JsonStore({
                data : this.data.states,
                model : modelName
            });

            this.statusStore = new Ext.data.JsonStore({
                data : this.data.statuses,
                model : modelName
            });

            this.actionsStore = new Ext.data.JsonStore({
                data : this.data.actions,
                model : modelName
            });

            this.addLayout();
        }
    },

    addLayout: function () {
        this.panel = new Ext.TabPanel({
            activeTab: 0,
            deferredRender: false,
            forceLayout: true,
            border: false,
            closable: true,
            autoScroll: true,
            title: this.data.name,
            iconCls : 'pimcore_icon_workflow',
            items: [
                this.getSettingsPanel(),
                this.getStatusPanel(),
                this.getStatesPanel(),
                this.getActionsPanel(),
                this.getTransitionDefinitionsPanel()
            ],
            buttons: [
                {
                    text: t("save"),
                    iconCls: "pimcore_icon_apply",
                    handler: this.save.bind(this)
                }
            ]
        });

        this.panel.on("destroy", function() {
            delete this.parentPanel.panels["workflow_" + this.id];
        }.bind(this));

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },

    getSettingsPanel : function() {
        if(!this.settingsPanel) {

            var typesStore = [['object', 'object'], ['asset', 'asset'], ['document', 'document']];

            var classesStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/class/get-tree'
                },
                fields: ['text']
            });
            classesStore.load();

            var assetTypeStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/class/get-asset-types'
                },
                fields: ["text"]
            });
            assetTypeStore.load();

            var documentTypeStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: '/admin/class/get-document-types'
                },
                fields: ["text"]
            });
            documentTypeStore.load();

            this.settingsPanel = new Ext.form.Panel({
                border: false,
                autoScroll: true,
                title: t('settings'),
                iconCls : 'pimcore_icon_settings',
                padding : 10,
                items: [
                    {
                        xtype : 'textfield',
                        name : 'name',
                        width: 500,
                        value : this.data.name,
                        fieldLabel : t('name')
                    },
                    {
                        xtype : 'checkbox',
                        name : 'enabled',
                        width: 500,
                        value : this.data.enabled,
                        fieldLabel : t('enabled')
                    },
                    {
                        xtype : 'checkbox',
                        name : 'allowUnpublished',
                        width: 500,
                        checked : this.data.allowUnpublished,
                        fieldLabel : t('allow_unpusblished')
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('default_state'),
                        name: 'defaultState',
                        value: this.data.defaultState,
                        width: 500,
                        store: this.statesStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        displayField: 'label',
                        valueField: 'name'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('default_status'),
                        name: 'defaultStatus',
                        value: this.data.defaultStatus,
                        width: 500,
                        store: this.statusStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        displayField: 'label',
                        valueField: 'name'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('types'),
                        name: 'types',
                        value: this.data.workflowSubject ? this.data.workflowSubject.types : [],
                        width: 500,
                        store: typesStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        multiSelect : true
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('allowed_classes'),
                        name: 'classes',
                        value: this.data.workflowSubject ? this.data.workflowSubject.classes : [],
                        width: 500,
                        store: classesStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        multiSelect : true,
                        valueField : 'id',
                        displayField : 'text'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('allowed_asset_types'),
                        name: 'assetTypes',
                        value: this.data.workflowSubject ? this.data.workflowSubject.assetTypes : [],
                        width: 500,
                        store: assetTypeStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        multiSelect : true
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('allowed_document_types'),
                        name: 'documentTypes',
                        value: this.data.workflowSubject ? this.data.workflowSubject.documentTypes : [],
                        width: 500,
                        store: documentTypeStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        multiSelect : true
                    }
                ]
            });
        }

        return this.settingsPanel;
    },

    getStatusPanel : function() {
        if(!this.statusPanel) {
            var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1
            });

            this.statusPanel = new Ext.Panel({
                border: false,
                autoScroll: true,
                title: t('statuses'),
                iconCls : 'pimcore_icon_workflow',
                items: [
                    {
                        xtype : 'grid',
                        margin: '0 0 15 0',
                        store :  this.statusStore,
                        plugins: [
                            cellEditing
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'name',
                                text : t('name'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'label',
                                text : t('label'),
                                field: {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'objectLayout',
                                text : t('custom_layout'),
                                width : 100,
                                field: {
                                    xtype: 'numberfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'elementPublished',
                                text : t('element_published'),
                                width : 100,
                                field: {
                                    xtype: 'checkbox'
                                }
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    Ext.MessageBox.prompt(t('add_workflow_status'), t('enter_the_name_of_the_new_workflow_status'),
                                        function(button, value) {
                                            if (button == "ok") {
                                                var u = {
                                                    name: value,
                                                    label: value
                                                };

                                                btn.up("grid").store.add(u);
                                            }
                                        }.bind(this)
                                    );
                                },
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    }
                ]
            });
        }

        return this.statusPanel;
    },

    getStatesPanel : function() {
        if(!this.statesPanel) {
            var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners : {
                    edit : function() {
                        this.updateStatusStoreForTransitionDefinitions();
                    }.bind(this)
                }
            });

            this.statesPanel = new Ext.Panel({
                border: false,
                autoScroll: true,
                title: t('states'),
                iconCls : 'pimcore_icon_workflow',
                items: [
                    {
                        xtype : 'grid',
                        margin: '0 0 15 0',
                        store :  this.statesStore,
                        plugins: [
                            cellEditing
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'name',
                                text : t('name'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'label',
                                text : t('label'),
                                field: {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'color',
                                text : t('color'),
                                width : 100,
                                field: {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    Ext.MessageBox.prompt(t('add_workflow_state'), t('enter_the_name_of_the_new_workflow_state'),
                                        function(button, value) {
                                            if (button == "ok") {
                                                var u = {
                                                    name: value,
                                                    label: value
                                                };

                                                btn.up("grid").store.add(u);

                                                this.updateStatusStoreForTransitionDefinitions();
                                            }
                                        }.bind(this)
                                    );
                                },
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    }
                ]
            });
        }

        return this.statesPanel;
    },

    getActionsPanel : function() {
        if(!this.actionsPanel) {
            this.actionsPanel = new Ext.Panel({
                border: false,
                autoScroll: true,
                title: t('actions'),
                iconCls : 'pimcore_icon_workflow',
                items: [
                    {
                        xtype : 'grid',
                        margin: '0 0 15 0',
                        store :  this.actionsStore,
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'name',
                                text : t('name'),
                                flex : 1
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'label',
                                text : t('label')
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_edit',
                                    tooltip: t('edit'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        this.editAction(grid.store.getAt(rowIndex));
                                    }.bind(this)
                                },{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    Ext.MessageBox.prompt(t('add_workflow_action'), t('enter_the_name_of_the_new_workflow_action'),
                                        function(button, value) {
                                            if (button == "ok") {
                                                var u = {
                                                    name: value,
                                                    label: value,
                                                    transitionTo : {},
                                                    notes : {
                                                        required: false
                                                    }
                                                };

                                                btn.up("grid").store.add(u);
                                            }
                                        }.bind(this)
                                    );
                                },
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    }
                ]
            });
        }

        return this.actionsPanel;
    },

    editAction : function(record, cb) {
        var transitions = {};
        var transitionTo = record.get("transitionTo");

        for(var state in transitionTo) {
            var statuses = transitionTo[state];

            transitions[state] = {
                state: state,
                status: []
            };

            for (var status in statuses) {
                if (statuses.hasOwnProperty(status)) {
                    transitions[state].status.push(statuses[status]);
                }
            }
        }

        var modelName = 'PimcoreWorkflowTranstionTo';
        if(!Ext.ClassManager.isCreated(modelName) ) {
            Ext.define(modelName, {
                extend: 'Ext.data.Model',
                idProperty: 'state'
            });
        }

        var transitionsStore = new Ext.data.JsonStore({
            data : $.map(transitions, function(value, index) {
                return [value];
            }),
            model : modelName
        });

        var cellEditingTransitions = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1,
            listeners : {
                edit : function() {
                    updateStateStore();
                }
            }
        });

        var cellEditingAdditionalFields = Ext.create('Ext.grid.plugin.CellEditing', {
            clicksToEdit: 1
        });

        var additionalFieldsStore = new Ext.data.JsonStore({
            data : record.get("additionalFields")
        });

        function updateStateStore() {
            allowedStatesStore.filterBy(function (r) {
                var id = r.data.name;

                if (!transitionsStore.getById(id)) {
                    return true;
                }

                return false;
            });

            if(allowedStatesStore.getRange().length <= 0) {
                window.down("button").disable();
            }
            else {
                window.down("button").enable();
            }
        };

        var usersStore = Ext.create('Ext.data.JsonStore', {
            proxy: {
                type: 'ajax',
                url: '/admin/user/tree-get-childs-by-id/'
            }
        });
        usersStore.load();

        var allowedStatesStore = this.deepCloneStore(this.statesStore);

        var events = Object.clone(record.get("events"));
        Ext.applyIf(events, {
            'before' : [], 'success' : [], 'failure' : []
        });

        for(var eventKey in events) {
            events[eventKey].splice(0, 0, eventKey);
        }

        var eventsStore = new Ext.data.ArrayStore({
            data : $.map(events, function(value, index) {
                return [value];
            }),
            fields : [
                'key',
                'class',
                'method'
            ]
        });

        var window = new Ext.window.Window({
            width : 800,
            height : 700,
            modal : true,
            resizeable : false,
            layout : 'fit',
            title : t('action'),
            items : [{
                xtype : 'form',
                bodyStyle:'padding:20px 5px 20px 5px;',
                border: false,
                autoScroll: true,
                forceLayout: true,
                fieldDefaults: {
                    labelWidth: 150
                },
                buttons: [
                    {
                        text: t('save'),
                        handler: function (btn) {
                            var window = this.up("window");
                            var grid = window.down("grid");

                            var name = window.down('[name="name"]').getValue();
                            var label = window.down('[name="label"]').getValue();
                            var notesRequired = window.down('[name="notesRequired"]').getValue();
                            var notesType = window.down('[name="notesType"]').getValue();
                            var notesTitle = window.down('[name="notesTitle"]').getValue();
                            var users = window.down('[name="users"]').getValue();
                            var notificationUsers = window.down('[name="notificationUsers"]').getValue();
                            var eventsRaw = eventsStore.getRange();
                            var eventsData = {};

                            eventsRaw.map(function(record) {
                                eventsData[record.get("key")] = [record.get("class"), record.get("method")]
                            });

                            var transitions = transitionsStore.getRange();
                            var additionFieldsRecords = additionalFieldsStore.getRange();
                            var additionalFields = additionFieldsRecords.map(function(record) {
                                return record.data;
                            });

                            var transitionsTo = {};

                            transitions.map(function(record) {
                                transitionsTo[record.get("state")] = record.get("status");
                            });

                            record.set("transitionTo", transitionsTo);
                            record.set("name", name);
                            record.set("label", label);
                            record.set("additionalFields", additionalFields);
                            record.set("notes", {
                                required : notesRequired,
                                title : notesTitle,
                                type : notesType
                            });
                            record.set("users", users);
                            record.set("notificationUsers", notificationUsers);
                            record.set("events", eventsData);

                            if(Ext.isFunction(cb)) {
                                cb.call(record)
                            }

                            window.close();
                        },
                        iconCls: 'pimcore_icon_apply'
                    }
                ],
                items : [
                    {
                        xtype : 'textfield',
                        name : 'name',
                        anchor : '100%',
                        value : record.get("name"),
                        fieldLabel : t('name')
                    },
                    {
                        xtype : 'textfield',
                        name : 'label',
                        anchor : '100%',
                        value : record.get("label"),
                        fieldLabel : t('label')
                    },
                    {
                        xtype : 'checkbox',
                        name : 'notesRequired',
                        anchor : '100%',
                        checked : record.get("notes").required,
                        fieldLabel : t('notes_required')
                    },
                    {
                        xtype : 'textfield',
                        name : 'notesType',
                        anchor : '100%',
                        value : record.get("notes").hasOwnProperty("type") ? record.get("notes").type : '',
                        fieldLabel : t('notes_type')
                    },
                    {
                        xtype : 'textfield',
                        name : 'notesTitle',
                        anchor : '100%',
                        value : record.get("notes").hasOwnProperty("title") ? record.get("notes").title : '',
                        fieldLabel : t('notes_title')
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('users'),
                        name: 'users',
                        value: record.get("users") ? record.get("users") : [],
                        width: 500,
                        store: usersStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        displayField: 'text',
                        valueField: 'id'
                    },
                    {
                        xtype: 'combo',
                        fieldLabel: t('notification_users'),
                        name: 'notificationUsers',
                        value: record.get("notificationUsers") ? record.get("notificationUsers") : [],
                        width: 500,
                        store: usersStore,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        displayField: 'text',
                        valueField: 'id'
                    },
                    {
                        xtype : 'grid',
                        title : t('events'),
                        margin: '0 0 15 0',
                        store :  eventsStore,
                        plugins: [
                            Ext.create('Ext.grid.plugin.CellEditing', {
                                clicksToEdit: 1
                            })
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'key',
                                text : t('key'),
                                flex : 1
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'class',
                                text : t('class'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'method',
                                text : t('method'),
                                field : {
                                    xtype: 'textfield'
                                }
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    },
                    {
                        xtype : 'grid',
                        title : t('transition_to'),
                        margin: '0 0 15 0',
                        store :  transitionsStore,
                        plugins: [
                            cellEditingTransitions
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'state',
                                text : t('state'),
                                flex : 1,
                                renderer : function(value) {
                                    var record = this.statesStore.getById(value);

                                    if(record) {
                                        return record.get("label");
                                    }

                                    return "";
                                }.bind(this),
                                field : {
                                    xtype: 'combo',
                                    name: 'types',
                                    width: 500,
                                    store: allowedStatesStore,
                                    triggerAction: 'all',
                                    typeAhead: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    displayField: 'label',
                                    valueField: 'name'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'status',
                                text : t('status'),
                                renderer : function(value) {
                                    if(Ext.isArray(value)) {
                                        var textValues = [];

                                        Ext.each(value, function(v) {
                                            var record = this.statusStore.getById(v);

                                            if(record) {
                                                textValues.push(record.get("label"));
                                            }
                                        }.bind(this));

                                        return textValues.join(", ");
                                    }
                                    var record = this.statusStore.getById(value);

                                    if(record) {
                                        return record.get("label");
                                    }

                                    return "";
                                }.bind(this),
                                field: {
                                    xtype: 'combo',
                                    name: 'types',
                                    width: 500,
                                    store: this.statusStore,
                                    triggerAction: 'all',
                                    typeAhead: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    multiSelect : true,
                                    displayField: 'label',
                                    valueField: 'name'
                                }
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);

                                        updateStateStore();
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    if(allowedStatesStore.getRange().length > 0) {
                                        var u = {
                                            state: '',
                                            status: ''
                                        };

                                        btn.up("grid").store.add(u);
                                    }
                                    else {
                                        Ext.Msg.alert(t('add_workflow_transition'), t('problem_creating_new_workflow_action_transition'));
                                    }
                                },
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    },
                    {
                        xtype : 'grid',
                        title : t('additional_fields'),
                        margin: '0 0 15 0',
                        store :  additionalFieldsStore,
                        plugins: [
                            cellEditingAdditionalFields
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'name',
                                text : t('name'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'fieldType',
                                text : t('field_type'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'title',
                                text : t('title'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'blankText',
                                text : t('blank_text'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'required',
                                text : t('mandatoryfield'),
                                flex : 1,
                                field : {
                                    xtype: 'checkbox'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'setterFn',
                                text : t('setter_function'),
                                flex : 1,
                                field : {
                                    xtype: 'textfield'
                                }
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);

                                        updateStateStore();
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    var u = {
                                        name: '',
                                        fieldType: '',
                                        title : '',
                                        blankText : '',
                                        required : false,
                                        setterFn : ''
                                    };

                                    btn.up("grid").store.add(u);
                                },
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    }
                ]
            }]
        });

        updateStateStore();

        window.show();
    },

    getTransitionDefinitionsPanel : function() {
        if(!this.transitionDefinitionsPanel) {
            var transitionDefinitions = {};
            var transitionDefinitionsRaw = this.data.transitionDefinitions;
            var globalTransition = [];

            for(var status in transitionDefinitionsRaw) {
                if(status === "globalActions") {
                    globalTransition = Object.keys(transitionDefinitionsRaw[status]);
                    continue;
                }

                var validActions = transitionDefinitionsRaw[status];

                transitionDefinitions[status] = {
                    state: status,
                    actions: []
                };

                for (var action in validActions['validActions']) {
                    if (validActions['validActions'].hasOwnProperty(action)) {
                        transitionDefinitions[status].actions.push(action);
                    }
                }
            }

            var modelName = 'PimcoreWorkflowTranstionDefinitionsTo';
            if(!Ext.ClassManager.isCreated(modelName) ) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    idProperty: 'state'
                });
            }

            var transitionDefinitionStore = this.transitionDefinitionStore = new Ext.data.JsonStore({
                data : $.map(transitionDefinitions, function(value, index) {
                    return [value];
                }),
                model : modelName
            });

            var cellEditingTransitions = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 1,
                listeners : {
                    edit : function() {
                        this.updateStatusStoreForTransitionDefinitions();
                    }.bind(this)
                }
            });

            this.allowedStatusStoreForTransitionDefinitons = this.deepCloneStore(this.statusStore);

            this.transitionDefinitionsPanel = new Ext.Panel({
                border: false,
                autoScroll: true,
                title: t('transition_definition'),
                iconCls : 'pimcore_icon_workflow',
                items: [
                    {
                        xtype: 'combo',
                        padding : 10,
                        fieldLabel : t('global_actions'),
                        name: 'globalActions',
                        width: 500,
                        store: this.actionsStore,
                        value : globalTransition,
                        triggerAction: 'all',
                        typeAhead: false,
                        editable: false,
                        forceSelection: true,
                        queryMode: 'local',
                        multiSelect : true,
                        displayField: 'label',
                        valueField: 'name'
                    },
                    {
                        xtype : 'grid',
                        title : t('transition_to'),
                        margin: '0 0 15 0',
                        store :  this.transitionDefinitionStore,
                        plugins: [
                            cellEditingTransitions
                        ],
                        sm: Ext.create('Ext.selection.RowModel', {}),
                        columns : [
                            {
                                xtype : 'gridcolumn',
                                dataIndex : 'state',
                                text : t('state'),
                                flex : 1,
                                renderer : function(value) {
                                    var record = this.statusStore.getById(value);

                                    if(record) {
                                        return record.get("label");
                                    }

                                    return "";
                                }.bind(this),
                                field : {
                                    xtype: 'combo',
                                    name: 'types',
                                    width: 500,
                                    store: this.allowedStatusStoreForTransitionDefinitons,
                                    triggerAction: 'all',
                                    typeAhead: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    displayField: 'label',
                                    valueField: 'name'
                                }
                            },
                            {
                                xtype : 'gridcolumn',
                                flex : 1,
                                dataIndex : 'actions',
                                text : t('actions'),
                                renderer : function(value) {
                                    if(Ext.isArray(value)) {
                                        var textValues = [];

                                        Ext.each(value, function(v) {
                                            var record = this.actionsStore.getById(v);

                                            if(record) {
                                                textValues.push(record.get("label"));
                                            }
                                        }.bind(this));

                                        return textValues.join(", ");
                                    }
                                    var record = this.actionsStore.getById(value);

                                    if(record) {
                                        return record.get("label");
                                    }

                                    return "";
                                }.bind(this),
                                field: {
                                    xtype: 'combo',
                                    name: 'types',
                                    width: 500,
                                    store: this.actionsStore,
                                    triggerAction: 'all',
                                    typeAhead: false,
                                    editable: false,
                                    forceSelection: true,
                                    queryMode: 'local',
                                    multiSelect : true,
                                    displayField: 'label',
                                    valueField: 'name'
                                }
                            },
                            {
                                menuDisabled: true,
                                sortable: false,
                                xtype: 'actioncolumn',
                                width: 50,
                                items: [{
                                    iconCls: 'pimcore_icon_delete',
                                    tooltip: t('delete'),
                                    handler: function (grid, rowIndex, colIndex) {
                                        grid.store.removeAt(rowIndex);

                                        this.updateStatusStoreForTransitionDefinitions();
                                    }.bind(this)
                                }]
                            }
                        ],
                        tbar: [
                            {
                                text:t('add'),
                                handler: function(btn) {
                                    if(this.allowedStatusStoreForTransitionDefinitons.getRange().length > 0) {
                                        var u = {
                                            state: '',
                                            status: ''
                                        };

                                        btn.up("grid").store.add(u);
                                    }
                                    else {
                                        Ext.Msg.alert(t('add_workflow'), t('problem_creating_new_workflow_transition_definition'));
                                    }
                                }.bind(this),
                                iconCls:"pimcore_icon_add"
                            }
                        ],
                        viewConfig:{
                            forceFit:true
                        }
                    }
                ]
            });

            this.updateStatusStoreForTransitionDefinitions();
        }

        return this.transitionDefinitionsPanel;
    },

    updateStatusStoreForTransitionDefinitions : function () {
        this.allowedStatusStoreForTransitionDefinitons.filterBy(function (r) {
            var id = r.data.name;

            if (!this.transitionDefinitionStore.getById(id)) {
                return true;
            }

            return false;
        }.bind(this));
    },

    save: function () {
        Ext.Ajax.request({
            url: "/admin/workflow-settings/update",
            method: "post",
            params: {
                data: this.getData(),
                id : this.id
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    getData: function () {
        var settings = this.settingsPanel.getForm().getFieldValues();
        var statuses = this.statusStore.getRange().map(function(record) {return record.data;});
        var states = this.statesStore.getRange().map(function(record) {return record.data;});
        var actions = this.actionsStore.getRange().map(function(record) {return record.data;});
        var transitionsDefinitions = this.transitionDefinitionStore.getRange();

        var transitionsDefinitionsData = {
            globalActions : {}
        };

        Ext.each(this.transitionDefinitionsPanel.down('[name="globalActions"]').getValue(), function(val) {
            transitionsDefinitionsData.globalActions[val] = null;
        });

        transitionsDefinitions.map(function(record) {
            transitionsDefinitionsData[record.get("state")] = {
                "validActions" : {}
            };

            Ext.each(record.get("actions"), function(val) {
                transitionsDefinitionsData[record.get("state")]["validActions"][val] = null;
            });
        });

        return Ext.JSON.encode({
            settings: settings,
            statuses : statuses,
            states : states,
            actions : actions,
            transitionDefinitions : transitionsDefinitionsData
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getStore().load({
            node: this.parentPanel.tree.getRootNode()
        });

        pimcore.helpers.showNotification(t("success"), t("workflow_saved_successfully"), "success");
    },

    activate: function () {
        this.parentPanel.getEditPanel().setActiveTab(this.panel);
    },

    deepCloneStore : function  (source) {
        source = Ext.isString(source) ? Ext.data.StoreManager.lookup(source) : source;

        var target = Ext.create(source.$className, {
            model: source.model
        });

        target.add(Ext.Array.map(source.getRange(), function (record) {
            return record.copy();
        }));

        return target;
    }
});