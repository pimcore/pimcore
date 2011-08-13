/**
 * Pimcore
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.pimcore.org/license
 *
 * @copyright  Copyright (c) 2009-2010 elements.at New Media Solutions GmbH (http://www.elements.at)
 * @license    http://www.pimcore.org/license     New BSD License
 */


pimcore.registerNS("pimcore.settings.user.panel");
pimcore.settings.user.panel = Class.create({

    documentTreeDataUrl: "/admin/document/get-tree-permissions/",
    assetTreeDataUrl: "/admin/asset/get-tree-permissions/",
    objectTreeDataUrl: "/admin/object/get-tree-permissions/",



    initialize: function () {
        Ext.Ajax.request({
            url: "/admin/user/get-available-permissions",
            success: function (transport) {
                this.availablePermissions = Ext.decode(transport.responseText);
            }.bind(this)
        });

        this.getTabPanel();
    },


    getAssetsPermissionEditor: function() {
        return new pimcore.settings.user.PermissionRowEditor({
            saveText: t('save'),
            cancelText: t('cancel'),
            resetText: t('permission_reset'),
            inheritText: t('permission_overwrite_children'),
            listeners : {
                afteredit: function(editor, changes, r, index, action) {

                    if (action == "save") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', false);
                        editor.grid.store.getAt(index).markDirty();


                    } else if (action == "reset") {
                        editor.grid.store.getAt(index).set('permissionSet', false);
                        editor.grid.store.getAt(index).set('list', true);
                        editor.grid.store.getAt(index).set('view', true);
                        editor.grid.store.getAt(index).set('delete', true);
                        editor.grid.store.getAt(index).set('publish', true);
                        editor.grid.store.getAt(index).set('rename', true);
                        editor.grid.store.getAt(index).set('settings', true);
                        editor.grid.store.getAt(index).set('create', true);
                        editor.grid.store.getAt(index).set('properties', true);
                        editor.grid.store.getAt(index).set('permissions', true);
                        editor.grid.store.getAt(index).set('versions', true);

                        editor.grid.store.getAt(index).markDirty();

                    } else if (action == "inherit") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', true);
                        editor.grid.store.getAt(index).markDirty();

                    }


                    editor.grid.store.save();

                }.bind(this),
                beforeedit: function(editor, rowindex, r) {

                    if (r.data.list_editable
                            || r.data.view_editable
                            || r.data.publish_editable
                            || r.data.createw_editable
                            || r.data.delete_editable
                            || r.data.rename_editable
                            || r.data.settings_editable
                            || r.data.properties_editable
                            || r.data.permissions_editable
                            || r.data.versions_editable
                            ) {
                        return true;
                    } else return false;
                }
            }
        });
    },


    getObjectPermissionsEditor: function() {
        return  new pimcore.settings.user.PermissionRowEditor({
            saveText: t('save'),
            cancelText: t('cancel'),
            resetText: t('permission_reset'),
            inheritText: t('permission_overwrite_children'),
            listeners : {
                afteredit: function(editor, changes, r, index, action) {

                    if (action == "save") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', false);
                        editor.grid.store.getAt(index).markDirty();


                    } else if (action == "reset") {
                        editor.grid.store.getAt(index).set('permissionSet', false);
                        editor.grid.store.getAt(index).set('list', true);
                        editor.grid.store.getAt(index).set('view', true);
                        editor.grid.store.getAt(index).set('save', true);
                        editor.grid.store.getAt(index).set('delete', true);
                        editor.grid.store.getAt(index).set('publish', true);
                        editor.grid.store.getAt(index).set('unpublish', true);
                        editor.grid.store.getAt(index).set('rename', true);
                        editor.grid.store.getAt(index).set('settings', true);
                        editor.grid.store.getAt(index).set('create', true);
                        editor.grid.store.getAt(index).set('properties', true);
                        editor.grid.store.getAt(index).set('permissions', true);
                        editor.grid.store.getAt(index).set('versions', true);

                        editor.grid.store.getAt(index).markDirty();

                    } else if (action == "inherit") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', true);
                        editor.grid.store.getAt(index).markDirty();

                    }

                    editor.grid.store.save();

                }.bind(this),
                beforeedit: function(editor, rowindex, r) {

                    if (r.data.list_editable
                            || r.data.view_editable
                            || r.data.save_editable
                            || r.data.publish_editable
                            || r.data.unpublish_editable
                            || r.data.createw_editable
                            || r.data.delete_editable
                            || r.data.rename_editable
                            || r.data.settings_editable
                            || r.data.properties_editable
                            || r.data.permissions_editable
                            || r.data.versions_editable
                            ) {
                        return true;
                    } else return false;
                }
            }
        });
    },

    getDocumentPermissionsEditor: function () {
        return new pimcore.settings.user.PermissionRowEditor({
            saveText: t('save'),
            cancelText: t('cancel'),
            resetText: t('permission_reset'),
            inheritText: t('permission_overwrite_children'),
            listeners : {
                afteredit: function(editor, changes, r, index, action) {

                    if (action == "save") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', false);
                        editor.grid.store.getAt(index).markDirty();


                    } else if (action == "reset") {
                        editor.grid.store.getAt(index).set('permissionSet', false);
                        editor.grid.store.getAt(index).set('list', true);
                        editor.grid.store.getAt(index).set('view', true);
                        editor.grid.store.getAt(index).set('save', true);
                        editor.grid.store.getAt(index).set('delete', true);
                        editor.grid.store.getAt(index).set('publish', true);
                        editor.grid.store.getAt(index).set('unpublish', true);
                        editor.grid.store.getAt(index).set('rename', true);
                        editor.grid.store.getAt(index).set('settings', true);
                        editor.grid.store.getAt(index).set('create', true);
                        editor.grid.store.getAt(index).set('properties', true);
                        editor.grid.store.getAt(index).set('permissions', true);
                        editor.grid.store.getAt(index).set('versions', true);

                        editor.grid.store.getAt(index).markDirty();

                    } else if (action == "inherit") {
                        editor.grid.store.getAt(index).set('permissionSet', true);
                        editor.grid.store.getAt(index).set('evictChildrenPermissions', true);
                        editor.grid.store.getAt(index).markDirty();

                    }

                    editor.grid.store.save();

                }.bind(this),
                beforeedit: function(editor, rowindex, r) {

                    if (r.data.list_editable
                            || r.data.view_editable
                            || r.data.save_editable
                            || r.data.publish_editable
                            || r.data.unpublish_editable
                            || r.data.createw_editable
                            || r.data.delete_editable
                            || r.data.rename_editable
                            || r.data.settings_editable
                            || r.data.properties_editable
                            || r.data.permissions_editable
                            || r.data.versions_editable
                            ) {
                        return true;
                    } else return false;
                }
            }
        });
    },

    getTabPanel: function () {




        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_users",
                title: t("users"),
                iconCls: "pimcore_icon_users",
                border: false,
                layout: "border",
                closable:true,
                items: [this.getUserTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_users");

            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("users");
            }.bind(this));

            pimcore.layout.refresh();


        }

        return this.panel;
    },

    getUserTree: function () {
        if (!this.tree) {
            this.tree = new Ext.tree.TreePanel({
                id: "pimcore_panel_users_tree",
                region: "west",
                useArrows:true,
                autoScroll:true,
                animate:true,
                enableDD:true,
                ddGroup: "users",
                containerScroll: true,
                rootVisible: true,
                border: true,
                split:true,
                width: 150,
                minSize: 100,
                maxSize: 350,
                root: {
                    nodeType: 'async',
                    text: t('all_users'),
                    draggable:false,
                    id: '0',
                    iconCls: "pimcore_icon_menu_users"
                },
                loader: new Ext.tree.TreeLoader({
                    dataUrl: '/admin/user/tree-get-childs-by-id/',
                    requestMethod: "GET",
                    baseAttrs: {
                        listeners: this.getTreeNodeListeners(),
                        reference: this,
                        allowDrop: true,
                        allowChildren: true,
                        isTarget: true
                    }
                })
            });


            this.tree.on("render", function () {
                this.getRootNode().expand();
            });


        }

        return this.tree;
    },

    getEditPanel: function () {
        if (!this.editPanel) {


            this.settingsPanel = new Ext.Panel({
                title: t('settings'),
                bodyStyle:'padding:10px;',
                layout: "fit",
                listeners: {
                    activate: function(panel) {
                        this.onTreeNodeClick();
                    }.bind(this)
                }
            });

            this.objectDependenciesPanel = new Ext.Panel({
                title: t('user_object_dependencies'),
                bodyStyle:'padding:10px',
                layout: "fit",
                listeners: {
                    activate: function(panel){
                        if (this.currentUser != null) {
                            this.addObjectDependenciesPanel(panel);
                        }
                    }.bind(this)
                }
            });

            this.documentPermissionsPanel = new Ext.Panel({
                title: t('documents_permissions'),
                id: 'user_document_permissions',
                bodyStyle:'padding:10px;',
                autoScroll: true,
                layout: "fit",
                listeners: {
                    activate: function(panel) {
                        if (this.currentUser != null) {
                            this.addDocumentsPermissionPanel(panel);
                        }
                    }.bind(this)
                }
            });

            this.assetPermissionsPanel = new Ext.Panel({
                title: t('assets_permissions'),
                id: 'user_assets_permissions',
                bodyStyle:'padding:10px;',
                layout: "fit",
                autoScroll: true,
                listeners: {
                    activate: function(panel) {
                        if (this.currentUser != null) {
                            this.addAssetsPermissionsPanel(panel);
                        }

                    }.bind(this)
                }
            });

            this.objectPermissionsPanel = new Ext.Panel({
                title: t('objects_permissions'),
                id: 'user_objects_permissions',
                bodyStyle:'padding:10px;',
                layout: "fit",
                autoScroll: true,
                listeners: {
                    activate: function(panel) {
                        if (this.currentUser != null) {
                            this.addObjectsPermissionsPanel(panel);
                        }

                    }.bind(this)
                }
            });

            var user = pimcore.globalmanager.get("user");
            var panelItems = []
            if (user.isAllowed("users")) {
                panelItems.push(this.settingsPanel);
                panelItems.push(this.objectDependenciesPanel);
            }
            if (user.isAllowed("documents")) {
                panelItems.push(this.documentPermissionsPanel);
            }
            if (user.isAllowed("assets")) {
                panelItems.push(this.assetPermissionsPanel);
            }
            if (user.isAllowed("objects")) {
                panelItems.push(this.objectPermissionsPanel);
            }

            this.editPanel = new Ext.TabPanel({
                activeTab: 0,
                items: panelItems,
                title: "&nbsp;",
                region: 'center'
            });


        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'click' : this.onTreeNodeClick,
            "contextmenu": this.onTreeNodeContextmenu,
            "move": this.onTreeNodeMove
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function () {

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("users")) {
            if (this.id > 0) {
                Ext.Ajax.request({
                    url: "/admin/user/get",
                    params: {
                        id: this.id
                    },
                    success: this.attributes.reference.addSettingsPanel.bind(this.attributes.reference)
                });
            }
        } else {
            if (this.id > 0) {
                Ext.Ajax.request({
                    url: "/admin/user/get-minimal",
                    params: {
                        id: this.id
                    },
                    success: this.attributes.reference.updatePermissionsPanelsOnly.bind(this.attributes.reference)
                });
            }
        }


    },

    updatePermissionsPanelsOnly: function(transport) {

        this.forceReloadOnSave = false;

        this.currentUser = Ext.decode(transport.responseText);
        var user = pimcore.globalmanager.get("user");
        if (this.currentUser != null && user.isAllowed("documents")) {
            this.addDocumentsPermissionPanel(this.documentPermissionsPanel);
        } else if (this.currentUser != null && user.isAllowed("assets")) {
            this.addAssetsPermissionsPanel(this.assetPermissionsPanel);
        } else if (this.currentUser != null && user.isAllowed("objects")) {
            this.addObjectsPermissionsPanel(this.objectPermissionsPanel);
        }

        this.updatePermissionsTabs(this.currentUser.permissionInfo.assets.granted, this.currentUser.permissionInfo.documents.granted, this.currentUser.permissionInfo.objects.granted);

        pimcore.layout.refresh();


    },



    addSettingsPanel: function (transport) {

        var user = pimcore.globalmanager.get("user");
        this.forceReloadOnSave = false;
        this.data = Ext.decode(transport.responseText);
        this.currentUser = this.data.user;
        this.wsenabled = this.data.wsenabled;


        if (this.userPanel) {
            this.settingsPanel.remove(this.userPanel);
        }

        var generalItems = new Array();

        generalItems.push(new Array({
            xtype: "checkbox",
            fieldLabel: t("active"),
            name: "active",
            checked: this.currentUser.active
        }));

        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("username"),
            value: this.currentUser.username,
            width: 300,
            disabled: true
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("password"),
            name: "password",
            inputType: "password",
            width: 300
        }));
        if(this.wsenabled && this.currentUser.admin){
            generalItems.push(new Array({
                xtype: "displayfield",
                hideLabel: true,
                width: 600,
                value: t("user_apikey_change_warning"),
                cls: "pimcore_extra_label_bottom"
            }));
        }

        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("firstname"),
            name: "firstname",
            value: this.currentUser.firstname,
            width: 300
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("lastname"),
            name: "lastname",
            value: this.currentUser.lastname,
            width: 300
        }));
        generalItems.push(new Array({
            xtype: "textfield",
            fieldLabel: t("email"),
            name: "email",
            value: this.currentUser.email,
            width: 300
        }));

        generalItems.push(new Array({
            xtype:'combo',
            fieldLabel: t('language'),
            typeAhead:true,
            value: this.currentUser.language,
            mode: 'local',
            listWidth: 100,
            store: pimcore.globalmanager.get("pimcorelanguages"),
            displayField: 'display',
            valueField: 'language',
            forceSelection: true,
            triggerAction: 'all',
            hiddenName: 'language',
            listeners: {
                change: function () {
                    this.forceReloadOnSave = true;
                }.bind(this),
                select: function () {
                    this.forceReloadOnSave = true;
                }.bind(this)
            }
        }));

        generalItems.push(new Array({
            xtype: "checkbox",
            fieldLabel: t("admin"),
            name: "admin",
            checked: this.currentUser.admin,
            disabled: !user.admin,
            handler: function (box, checked) {
                var pfs = Ext.getCmp("users_permissions_fieldset");
                var childs = pfs.findByType("checkbox");
                if (checked == true) {
                    pfs.disable();
                }
                else {
                    pfs.enable();
                }

                for (var i = 0; i < childs.length; i++) {
                    childs[i].setValue(checked);
                }
            }
        }));

        generalItems.push(new Array({
            xtype: "displayfield",
            hideLabel: true,
            width: 600,
            value: t("user_admin_description"),
            cls: "pimcore_extra_label_bottom"
        }));

         if(this.wsenabled && this.currentUser.admin){

            generalItems.push(new Array({
                xtype: "displayfield",
                fieldLabel: t("apikey"),
                name: "apikey",
                value: this.currentUser.password,
                width: 300
            }));

            generalItems.push(new Array({
                xtype: "displayfield",
                hideLabel: true,
                width: 600,
                value: t("user_apikey_description"),
                cls: "pimcore_extra_label_bottom"
            }));
        }



        var userItems = new Array();

        if (this.currentUser.hasCredentials) {
            userItems.push(new Array({
                xtype: "fieldset",
                title: t("general"),
                items: generalItems
            }));
        }

        var availPermsItems = {
            id: "users_permissions_fieldset",
            xtype: "fieldset",
            title: t("permissions"),
            items: [],
            disabled: this.currentUser.admin
        };

        // add available permissions
        for (var i = 0; i < this.availablePermissions.length; i++) {
            availPermsItems.items.push({
                xtype: "checkbox",
                fieldLabel: t(this.availablePermissions[i].translation),
                name: this.availablePermissions[i].key,
                checked: this.currentUser.permissionInfo[this.availablePermissions[i].key].granted,
                disabled: this.currentUser.permissionInfo[this.availablePermissions[i].key].inherited,
                labelStyle: "width: 200px;"
            });
        }

        userItems.push(availPermsItems);

        this.userPanel = new Ext.form.FormPanel({
            border: false,
            padding: 10,
            layout: "pimcoreform",
            items: userItems,
            buttons: [
                {
                    text: t("save"),
                    handler: this.saveCurrentUser.bind(this),
                    iconCls: "pimcore_icon_apply"
                }
            ],
            autoScroll: true
        });

        this.settingsPanel.add(this.userPanel);
        this.editPanel.setTitle(t("user") + ": " + this.currentUser.username);
        this.editPanel.activate(0);

        if(this.currentUser.admin && !user.admin) {
            this.editPanel.disable();
        } else {
            this.editPanel.enable();
        }


        this.updatePermissionsTabs(this.currentUser.permissionInfo.assets.granted, this.currentUser.permissionInfo.documents.granted, this.currentUser.permissionInfo.objects.granted);

        pimcore.layout.refresh();
    },



    getStoreForUrl: function(dataUrl, record) {
        return new Ext.ux.maximgb.tg.AdjacencyListStore({
            autoLoad : false,
            autoSave: false,
            autoDestroy: false,
            remoteSort: true,
            baseParams: {
                user: this.currentUser.id
            },
            //url: dataUrl,
            idProperty: '_id',
            proxy: new Ext.data.HttpProxy({
                url: dataUrl,
                method: 'post'
            }),
            writer : new Ext.data.JsonWriter(),
            listeners: {
                save: function(store) {
                    this.reload();
                },
                beforeexpandnode: function(store, record){

                    var parent = store.getById( record.data._parent );
                    if(parent!== undefined){
                        var children = store.getNodeChildren(parent) ;
                        if(children.length > 0){
                            for(var i = 0; i<children.length; i++){
                                if(children[i].data._id != record.data._id){
                                    store.collapseNode(children[i]);
                                }
                            }
                        }
                    }
                     store.setActiveNode(record);
                },

                beforeload: function(store) {
                    var markDirtyRecursive = function (store, n, fn) {
                        if (n != null && n !== undefined) {
                            var children = store.getNodeChildren(n);
                            if (children.length > 0) {
                                for (var i = 0; i < children.length; i++) {
                                    fn(store, children[i], fn);
                                    children[i].markDirty();
                                }
                            }
                        }
                    }
                    markDirtyRecursive(store, store.getActiveNode(), markDirtyRecursive);
                    var visibleNodes = "";
                    for(var i = 0; i<store.data.items.length; i++){
                        if(store.isVisibleNode(store.data.items[i])){
                            if(visibleNodes != ""){
                                visibleNodes=visibleNodes + ',';
                            }
                            visibleNodes=visibleNodes + store.data.items[i].data._id;
                        }
                    }
                    store.setBaseParam("visible", visibleNodes);

                },
                load: function(store) {
                    try {
                        var active = store.getActiveNode();
                        if (active != null) {
                            var nodesToExpand = [];
                            var parent = store.getById(active.data._parent);
                            while (parent !== undefined) {
                                nodesToExpand.push(parent);
                                parent = store.getById(parent.data._parent);
                            }
                            for (var i = nodesToExpand.length - 1; i >= 0; i--) {
                                store.expandNode(nodesToExpand[i]);
                            }


                        }
                    } catch(e) {
                        console.log(e);
                    }
                }/*,
                metachange: function() {
                    console.log("metachange");
                },
                update: function() {
                    console.log("update");

                },
                write: function() {
                    console.log("write");
                },
                datachanged: function() {
                    console.log("datachanged");
                }*/
            },
            reader: new Ext.data.JsonReader(
            {
                id: '_id',
                root: 'data',
                totalProperty: 'total',
                successProperty: 'success'
            },
                    record
                    )
        });
    },

    getCheckColumn:function(permission) {


        return new Ext.grid.BooleanColumn({
            header: t(permission),
            dataIndex: permission,
            align: 'center',
            width: 50,
            trueText: '<div class="permission_checked">&nbsp;</div>',
            falseText: '-',
            editor: {
                xtype: 'checkbox'


            }
        });
    },

    addObjectDependenciesPanel: function(panel){

         this.objectDependenciesStore = new Ext.data.JsonStore({
            autoDestroy: true,
            data: this.data.objectDependencies,
            root: 'dependencies',
            fields: ['id', 'path', 'subtype']
        });

        this.objectDependenciesGrid = new Ext.grid.GridPanel({
            store: this.objectDependenciesStore,
            columns: [
                {header: "ID", sortable: true, dataIndex: 'id'},
                {header: t("path"), id: "path", sortable: true, dataIndex: 'path'},
                {header: t("subtype"), sortable: true, dataIndex: 'subtype'}
            ],
            columnLines: true,
            autoExpandColumn: "path",
            stripeRows: true,
            autoHeight: true,
            title: t('user_object_dependencies_description')
        });
        this.objectDependenciesGrid.on("rowclick", function(grid, index){
                var d = grid.getStore().getAt(index).data;
                pimcore.helpers.openObject(d.id, "object");

        });

        this.hiddenNote = new Ext.Panel({
            html:t('hidden_dependencies'),
            cls:'dependency-warning',
            border:false,
            hidden: !this.data.objectDependencies.hasHidden
        });

        panel.removeAll();
        panel.add(this.objectDependenciesGrid);
        panel.add(this.hiddenNote);
        pimcore.layout.refresh();
    },

    addDocumentsPermissionPanel: function(panel) {
        this.documentPermissionsEditor = this.getDocumentPermissionsEditor();
        var record = Ext.data.Record.create([
            {name: 'text'},
            {name: 'iconCls'},
            {name: 'list', type: 'bool'},
            {name: 'view', type: 'bool'},
            {name: 'save', type: 'bool'},
            {name: 'publish', type: 'bool'},
            {name: 'unpublish', type: 'bool'},
            {name: 'delete', type: 'bool'},
            {name: 'rename', type: 'bool'},
            {name: 'create', type: 'bool'},
            {name: 'settings', type: 'bool'},
            {name: 'properties', type: 'bool'},
            {name: 'permissions', type: 'bool'},
            {name: 'versions', type: 'bool'},
            {name: 'list_editable', type: 'bool'},
            {name: 'view_editable', type: 'bool'},
            {name: 'save_editable', type: 'bool'},
            {name: 'publish_editable', type: 'bool'},
            {name: 'unpublish_editable', type: 'bool'},
            {name: 'delete_editable', type: 'bool'},
            {name: 'rename_editable', type: 'bool'},
            {name: 'create_editable', type: 'bool'},
            {name: 'settings_editable', type: 'bool'},
            {name: 'properties_editable', type: 'bool'},
            {name: 'permissions_editable', type: 'bool'},
            {name: 'versions_editable', type: 'bool'},
            {name: '_id', type: 'int'},
            {name: '_parent', type: 'auto'},
            {name: '_is_leaf', type: 'bool'},
            {name: 'permissionSet', type: 'bool'},
            {name: 'evictChildrenPermissions', type: 'bool'}
        ]);
        this.documentPermissionsStore = this.getStoreForUrl(this.documentTreeDataUrl, record);


        var listCheckColumn = this.getCheckColumn("list");
        var viewCheckColumn = this.getCheckColumn("view");
        var saveCheckColumn = this.getCheckColumn("save");
        var publishCheckColumn = this.getCheckColumn("publish");
        var unpublishCheckColumn = this.getCheckColumn("unpublish");
        var createCheckColumn = this.getCheckColumn("create");
        var deleteCheckColumn = this.getCheckColumn("delete");
        var renameCheckColumn = this.getCheckColumn("rename");
        var settingsCheckColumn = this.getCheckColumn("settings");
        var propertiesCheckColumn = this.getCheckColumn("properties");
        var permissionsCheckColumn = this.getCheckColumn("permissions");
        var versionsCheckColumn = this.getCheckColumn("versions");


        // create the Grid
        this.documentPermissionsGrid = new Ext.ux.maximgb.tg.EditorGridPanel({
            store: this.documentPermissionsStore,
            loadMask:true,
            master_column_id : '_id',
            plugins : [
                this.documentPermissionsEditor],

            columns: [
                {id:'_id',header: t('documents'), width: 100, sortable: false, dataIndex: '_id', renderer: this.renderPermissionMasterColum},
                listCheckColumn,
                viewCheckColumn,
                saveCheckColumn,
                publishCheckColumn,
                unpublishCheckColumn,
                createCheckColumn,
                deleteCheckColumn,
                renameCheckColumn,
                settingsCheckColumn,
                propertiesCheckColumn,
                permissionsCheckColumn,
                versionsCheckColumn
            ],
            autoExpandColumn: '_id'


        });

        this.documentPermissionsStore.load();
        panel.removeAll();
        panel.add(this.documentPermissionsGrid);
        pimcore.layout.refresh();
    },


    addAssetsPermissionsPanel: function(panel) {

        this.assetPermissionsEditor = this.getAssetsPermissionEditor();

        var record = Ext.data.Record.create([
            {name: 'text'},
            {name: 'iconCls'},
            {name: 'list', type: 'bool'},
            {name: 'view', type: 'bool'},
            {name: 'publish', type: 'bool'},
            {name: 'delete', type: 'bool'},
            {name: 'rename', type: 'bool'},
            {name: 'create', type: 'bool'},
            {name: 'settings', type: 'bool'},
            {name: 'properties', type: 'bool'},
            {name: 'permissions', type: 'bool'},
            {name: 'versions', type: 'bool'},
            {name: 'list_editable', type: 'bool'},
            {name: 'view_editable', type: 'bool'},
            {name: 'publish_editable', type: 'bool'},
            {name: 'delete_editable', type: 'bool'},
            {name: 'rename_editable', type: 'bool'},
            {name: 'create_editable', type: 'bool'},
            {name: 'settings_editable', type: 'bool'},
            {name: 'properties_editable', type: 'bool'},
            {name: 'permissions_editable', type: 'bool'},
            {name: 'versions_editable', type: 'bool'},
            {name: '_id', type: 'int'},
            {name: '_parent', type: 'auto'},
            {name: '_is_leaf', type: 'bool'},
            {name: 'permissionSet', type: 'bool'},
            {name: 'evictChildrenPermissions', type: 'bool'}
        ]);
        this.assetPermissionsStore = this.getStoreForUrl(this.assetTreeDataUrl, record);


        var listCheckColumn = this.getCheckColumn("list");
        var viewCheckColumn = this.getCheckColumn("view");
        var publishCheckColumn = this.getCheckColumn("publish");
        var createCheckColumn = this.getCheckColumn("create");
        var deleteCheckColumn = this.getCheckColumn("delete");
        var renameCheckColumn = this.getCheckColumn("rename");
        var settingsCheckColumn = this.getCheckColumn("settings");
        var propertiesCheckColumn = this.getCheckColumn("properties");
        var permissionsCheckColumn = this.getCheckColumn("permissions");
        var versionsCheckColumn = this.getCheckColumn("versions");


        // create the Grid
        this.assetPermissionsGrid = new Ext.ux.maximgb.tg.EditorGridPanel({
            store: this.assetPermissionsStore,
            loadMask:true,
            master_column_id : '_id',
            plugins : [
                this.assetPermissionsEditor ],
            columns: [
                {id:'_id',header: t('assets'), width: 160, sortable: false, dataIndex: '_id', renderer: this.renderPermissionMasterColum},
                listCheckColumn,
                viewCheckColumn,
                publishCheckColumn,
                createCheckColumn,
                deleteCheckColumn,
                renameCheckColumn,
                settingsCheckColumn,
                propertiesCheckColumn,
                permissionsCheckColumn,
                versionsCheckColumn
            ],
            autoExpandColumn: '_id'

        });
        this.assetPermissionsStore.load();
        panel.removeAll();
        panel.add(this.assetPermissionsGrid);
        pimcore.layout.refresh();

    },

    addObjectsPermissionsPanel: function(panel) {

        this.objectPermissionsEditor = this.getObjectPermissionsEditor();

        var record = Ext.data.Record.create([
            {name: 'text'},
            {name: 'iconCls'},
            {name: 'list', type: 'bool'},
            {name: 'view', type: 'bool'},
            {name: 'save', type: 'bool'},
            {name: 'publish', type: 'bool'},
            {name: 'unpublish', type: 'bool'},
            {name: 'delete', type: 'bool'},
            {name: 'rename', type: 'bool'},
            {name: 'create', type: 'bool'},
            {name: 'settings', type: 'bool'},
            {name: 'properties', type: 'bool'},
            {name: 'permissions', type: 'bool'},
            {name: 'versions', type: 'bool'},
            {name: 'list_editable', type: 'bool'},
            {name: 'view_editable', type: 'bool'},
            {name: 'save_editable', type: 'bool'},
            {name: 'publish_editable', type: 'bool'},
            {name: 'unpublish_editable', type: 'bool'},
            {name: 'delete_editable', type: 'bool'},
            {name: 'rename_editable', type: 'bool'},
            {name: 'create_editable', type: 'bool'},
            {name: 'settings_editable', type: 'bool'},
            {name: 'properties_editable', type: 'bool'},
            {name: 'permissions_editable', type: 'bool'},
            {name: 'versions_editable', type: 'bool'},
            {name: '_id', type: 'int'},
            {name: '_parent', type: 'auto'},
            {name: '_is_leaf', type: 'bool'},
            {name: 'permissionSet', type: 'bool'},
            {name: 'evictChildrenPermissions', type: 'bool'}
        ]);
        this.objectPermissionsStore = this.getStoreForUrl(this.objectTreeDataUrl, record);


        var listCheckColumn = this.getCheckColumn("list");
        var viewCheckColumn = this.getCheckColumn("view");
        var saveCheckColumn = this.getCheckColumn("save");
        var publishCheckColumn = this.getCheckColumn("publish");
        var unpublishCheckColumn = this.getCheckColumn("unpublish");
        var createCheckColumn = this.getCheckColumn("create");
        var deleteCheckColumn = this.getCheckColumn("delete");
        var renameCheckColumn = this.getCheckColumn("rename");
        var settingsCheckColumn = this.getCheckColumn("settings");
        var propertiesCheckColumn = this.getCheckColumn("properties");
        var permissionsCheckColumn = this.getCheckColumn("permissions");
        var versionsCheckColumn = this.getCheckColumn("versions");


        // create the Grid
        this.objectPermissionsGrid = new Ext.ux.maximgb.tg.EditorGridPanel({
            loadMask:true,
            store: this.objectPermissionsStore,
            master_column_id : '_id',
            plugins : [
                this.objectPermissionsEditor
            ],
            columns: [

                {id:'_id',header: t('objects'), width: 160, sortable: false, dataIndex: '_id', renderer: this.renderPermissionMasterColum},
                listCheckColumn,
                viewCheckColumn,
                saveCheckColumn,
                publishCheckColumn,
                unpublishCheckColumn,
                createCheckColumn,
                deleteCheckColumn,
                renameCheckColumn,
                settingsCheckColumn,
                propertiesCheckColumn,
                permissionsCheckColumn,
                versionsCheckColumn
            ],
            autoExpandColumn: '_id'

        });
        this.objectPermissionsStore.load();
        panel.removeAll();
        panel.add(this.objectPermissionsGrid);
        pimcore.layout.refresh();
    },

    renderFormColumn: function(value, p, r) {
        return new Ext.form.FormPanel({
            border: false,
            items: [],
            buttons: [
                {
                    text: t("save"),
                    handler: function() {
                    }
                }
            ],
            autoScroll: true
        });
    },

    renderPermissionMasterColum: function(value, p, r) {

        var retVal = "";
        if (r.data._id == 1) {
            retVal = '<div style="float:left;height: 20px; width: 20px" class="pimcore_icon_home">&nbsp;</div><div  style="padding: 3px 0 0 0;float:left;">home (1)</div>';
        } else {
            retVal = '<div style="float:left;height: 20px; width: 20px" class="' + r.data.iconCls + '">&nbsp;</div><div  style="padding: 3px 0 0 0;float:left;">' + r.data.text + ' (' + r.data._id + ')' + '</div>';
        }
        if (r.data.list_editable
                || r.data.view_editable
                || r.data.save_editable
                || r.data.publish_editable
                || r.data.unpublish_editable
                || r.data.createw_editable
                || r.data.delete_editable
                || r.data.rename_editable
                || r.data.settings_editable
                || r.data.properties_editable
                || r.data.permissions_editable
                || r.data.versions_editable
                ) {
            var permissionEditCls = 'permission_add';
            if (r.data.permissionSet) {
                permissionEditCls = 'permission_edit';
            }
            retVal = retVal + '<div class="' + permissionEditCls + '" style="float:right;">&nbsp;</div>';
        }
        return retVal;
    },

    onTreeNodeMove: function (tree, element, oldParent, newParent, index) {
        this.attributes.reference.updateUser(this.id, {
            parentId: newParent.id
        });
    },

    onTreeNodeContextmenu: function () {

        var user = pimcore.globalmanager.get("user");
        if (user.isAllowed("users")) {

            this.select();
            var menu = new Ext.menu.Menu();

            if (this.allowChildren) {
                menu.add(new Ext.menu.Item({
                    text: t('add_user_group'),
                    iconCls: "pimcore_icon_usergroup_add",
                    listeners: {
                        "click": this.attributes.reference.addUserGroup.bind(this)
                    }
                }));
                menu.add(new Ext.menu.Item({
                    text: t('add_user'),
                    iconCls: "pimcore_icon_user_add",
                    listeners: {
                        "click": this.attributes.reference.addUser.bind(this)
                    }
                }));
            }


            if (this.childNodes == 0 && !this.allowChildren && this.id != user.id) {
                // users
                menu.add(new Ext.menu.Item({
                    text: t('delete_user'),
                    iconCls: "pimcore_icon_user_delete",
                    listeners: {
                        "click": this.attributes.reference.deleteUser.bind(this)
                    }
                }));
            } else if(this.allowChildren) {
                // groups
                var isEnabled = true;
                if (this.childNodes == 0) {
                    isEnabled = false;
                }
                menu.add(new Ext.menu.Item({
                    text: t('delete_user_group'),
                    iconCls: "pimcore_icon_usergroup_delete",
                    listeners: {
                        "click": this.attributes.reference.deleteUser.bind(this)
                    },
                    disabled: isEnabled
                }));
            }

            if(typeof menu.items != "undefined" && typeof menu.items.items != "undefined" && menu.items.items.length > 0) {
                menu.show(this.ui.getAnchor());
            }
        }
    },

    addUser: function () {

        Ext.MessageBox.prompt(t('add_user'), t('please_enter_the_username'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        //parentId: 0,
                        username: value,
                        hasCredentials: 1,
                        active: 1
                    },
                    success: this.attributes.reference.addUserComplete.bind(this.attributes.reference)
                });
            }
        }.bind(this));
    },

    addUserGroup: function () {
        Ext.MessageBox.prompt(t('add_user_group'), t('please_enter_the_usergroupname'), function (button, value, object) {
            if(button=='ok' && value != ''){
                Ext.Ajax.request({
                    url: "/admin/user/add",
                    params: {
                        parentId: this.id,
                        //parentId: 0,
                        username: value,
                        hasCredentials: 0,
                        active: 1
                    },
                    success: this.attributes.reference.addUserComplete.bind(this.attributes.reference)
                });
            }
        }.bind(this));
    },

    addUserComplete: function (transport) {
        try{
            var data = Ext.decode(transport.responseText);
            if(data && data.success){
                var icon = "pimcore_icon_user";
                if (!data.hasCredentials) {
                    icon = "pimcore_icon_usergroup"
                }
                var node = new Ext.tree.TreeNode({
                    listeners: this.getTreeNodeListeners(),
                    reference: this,
                    allowDrop: true,
                    allowChildren: !data.hasCredentials,
                    isTarget: true,
                    text: data.username,
                    id: data.id,
                    iconCls: icon
                });
                var insertedNode = this.tree.getNodeById(data.parentId).appendChild(node);
                try{  
                    insertedNode.fireEvent("click");
                } catch (e){}
            } else {
                
                 pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error",t(data.message));
            }

        } catch(e){

             pimcore.helpers.showNotification(t("error"), t("user_creation_error"), "error")
        }
    },

    deleteUser: function () {
        Ext.Ajax.request({
            url: "/admin/user/delete",
            params: {
                id: this.id
            }
        });

        this.remove();
    },

    updatePermissionsTabs: function(assetsAllowed, documentsAllowed, objectsAllowed) {

        if (this.assetPermissionsPanel && assetsAllowed) {
            this.assetPermissionsPanel.enable();
        } else {
            this.assetPermissionsPanel.disable();
        }
        if (this.documentPermissionsPanel && documentsAllowed) {
            this.documentPermissionsPanel.enable();
        } else {
            this.documentPermissionsPanel.disable();
        }
        if (this.objectPermissionsPanel && objectsAllowed) {
            this.objectPermissionsPanel.enable();
        } else {
            this.objectPermissionsPanel.disable();
        }
    },

    saveCurrentUser: function () {
        var values = this.userPanel.getForm().getFieldValues();
        this.updateUser(this.currentUser.id, values);
        this.updatePermissionsTabs(values.assets == "on", values.documents == "on", values.objects == "on");
    },

    updateUser: function (userId, values) {

        Ext.Ajax.request({
            url: "/admin/user/update",
            method: "post",
            params: {
                id: userId,
                data: Ext.encode(values)
            },
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        if(this.forceReloadOnSave) {
                            this.forceReloadOnSave = false;

                            // only if the current user is equal to the edited user
                            var user = pimcore.globalmanager.get("user");
                            if(this.currentUser.id == user.id) {
                                Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                                    if (buttonValue == "yes") {
                                        window.location.reload();
                                    }
                                }.bind(this));
                            }
                        }
                        pimcore.helpers.showNotification(t("success"), t("user_save_success"), "success");
                    } else {
                        pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error",t(res.message));
                    }
                } catch(e){
                    pimcore.helpers.showNotification(t("error"), t("user_save_error"), "error");
                }
                this.updatePermissionsTabs(values.assets,values.documents,values.objects);
            }.bind(this)
        });
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").activate("pimcore_users");
    }

});





