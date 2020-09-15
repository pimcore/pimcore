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

pimcore.registerNS("pimcore.object.klass");
pimcore.object.klass = Class.create({

    forbiddenNames: [
        "abstract", "class", "data", "folder", "list", "permissions", "resource", "concrete", "interface",
        "service", "fieldcollection", "localizedfield", "objectbrick"
    ],

    initialize: function () {

        this.getTabPanel();
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.Panel({
                id: "pimcore_classes",
                title: t("classes"),
                iconCls: "pimcore_icon_class",
                border: false,
                layout: "border",
                closable: true,
                items: [this.getClassTree(), this.getEditPanel()]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.setActiveItem("pimcore_classes");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("classes");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    getClassTree: function () {
        if (!this.tree) {
            this.store = Ext.create('Ext.data.TreeStore', {
                autoLoad: false,
                autoSync: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('pimcore_admin_dataobject_class_gettree'),
                    reader: {
                        type: 'json'

                    },
                    extraParams: {
                        grouped: 1,
                        withId: 1
                    }
                }
            });


            this.tree = Ext.create('Ext.tree.Panel', {
                id: "pimcore_panel_classes_tree",
                store: this.store,
                region: "west",
                autoScroll: true,
                animate: false,
                containerScroll: true,
                width: 250,
                split: true,
                root: {
                    id: '0'
                },
                listeners: this.getTreeNodeListeners(),
                rootVisible: false,
                tbar: {
                    cls: 'pimcore_toolbar_border_bottom',
                    items: [
                        {
                            text: t("add"),
                            iconCls: "pimcore_icon_class pimcore_icon_overlay_add",
                            handler: this.suggestIdentifier.bind(this)
                        }
                    ]
                }
            });

            this.tree.on("render", function () {
                this.getRootNode().expand();
            });
        }

        return this.tree;
    },

    suggestIdentifier: function() {
        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_class_suggestclassidentifier'),
            params: {
            },
            success: function (response) {
                var classes = Ext.decode(response.responseText);
                this.addClass(classes);
            }.bind(this)
        });

    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                region: "center",
                plugins:
                    [
                        Ext.create('Ext.ux.TabCloseMenu', {
                            showCloseAll: true,
                            showCloseOthers: true
                        }),
                        Ext.create('Ext.ux.TabReorderer', {})
                    ],
                listeners: {
                    'tabchange': function (tabpanel, tab) {
                        var classId = tab.id.substr("pimcore_class_editor_panel_".length);
                        var tree = this.tree;
                        this.tree.getRootNode().cascade(function () {
                            if (this.id.toString() === classId) {
                                tree.setSelection(this);
                            }
                        });
                    }.bind(this)
                }
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick': this.onTreeNodeClick.bind(this),
            "itemcontextmenu": this.onTreeNodeContextmenu.bind(this)
        };

        return treeNodeListeners;
    },

    onTreeNodeClick: function (tree, record, item, index, e, eOpts) {
        if (!record.isLeaf()) {
            return;
        }

        this.openClass(record.data.id);
    },

    openClass: function (id) {
        if (Ext.getCmp("pimcore_class_editor_panel_" + id)) {
            this.getEditPanel().setActiveTab(Ext.getCmp("pimcore_class_editor_panel_" + id));
            return;
        }

        if (id && id.length > 0) {
            Ext.Ajax.request({
                url: Routing.generate('pimcore_admin_dataobject_class_get'),
                params: {
                    id: id
                },
                success: this.addClassPanel.bind(this)
            });
        }
    },

    addClassPanel: function (response) {

        var data = Ext.decode(response.responseText);

        /*if (this.classPanel) {
         this.getEditPanel().removeAll();
         delete this.classPanel;
         }*/

        var classPanel = new pimcore.object.classes.klass(data, this, this.openClass.bind(this, data.id), "pimcore_class_editor_panel_");
        pimcore.layout.refresh();
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();
        tree.select();

        if (!record.isLeaf()) {
            return;
        }


        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "pimcore_icon_class pimcore_icon_overlay_delete",
            handler: this.deleteClass.bind(this, tree, record)
        }));


        menu.showAt(e.pageX, e.pageY);
    },

    addClass: function (classes) {

        var suggestedIdentifier = classes["suggestedIdentifier"];
        var nameField = new Ext.form.field.Text(
            {
                fieldLabel: 'Class name',
                labelWidth: 200
            }
        );

        var identifierField = new Ext.form.field.Text({
            fieldLabel: t('unique_identifier'),
            labelWidth: 200,
            maxLength: 20,
            value: suggestedIdentifier
        });

        this.win = new Ext.Window({
            title: t('enter_the_name_of_the_new_item'),
            width: 400,
            height: 250,
            draggable: false,
            border: false,
            modal: true,
            bodyStyle: "padding: 10px;",
            resizable: true,
            buttonAlign: 'center',
            items: [
                nameField,
                identifierField, {
                    xtype: 'panel',
                    html: t('identifier_warning')
                }
            ],
            buttons: [
                {
                    xtype: 'button',
                    text: t('cancel'),
                    iconCls: 'pimcore_icon_cancel',
                    handler: function () {
                        this.win.close();

                    }.bind(this)
                },
                {
                    xtype: 'button',
                    text: t('OK'),
                    iconCls: 'pimcore_icon_save',
                    handler: function ( nameField, identifierField, classes, button) {
                        if (this.addClassComplete(nameField.getValue(), identifierField.getValue(), classes)) {
                            this.win.close();
                        }
                    }.bind(this, nameField, identifierField, classes)
                }
            ]
        })
        this.win.show();
        nameField.focus();

    },

    addClassComplete: function (className, classIdentifier, classes) {

        var isReservedName = /^(query|store|relations)_[^_]+$/;
        var isValidClassName = /^[a-zA-Z][a-zA-Z0-9_]+$/;
        var isValidClassIdentifier = /^[a-zA-Z0-9][a-zA-Z0-9_]*$/;

        if (className.length <= 2 ||
            isReservedName.test(className) ||
            !isValidClassName.test(className) ||
            in_arrayi(className, this.forbiddenNames)
        ) {
            Ext.Msg.alert(' ', t('invalid_class_name'));
            return false;
        }

        if (classIdentifier.length < 1 ||
            isReservedName.test(classIdentifier) ||
            !isValidClassIdentifier.test(classIdentifier)
        ) {
            Ext.Msg.alert(' ', t('invalid_identifier'));
            return false;
        }

        if (in_arrayi(classIdentifier, classes["existingIds"])) {
            Ext.Msg.alert(' ', t('identifier_already_exists'));
            return false;
        }

        Ext.Ajax.request({
            url: Routing.generate('pimcore_admin_dataobject_class_add'),
            method: "POST",
            params: {
                className: className,
                classIdentifier: classIdentifier
            },
            success: function (response) {

                this.tree.getStore().load();

                // update object type store
                pimcore.globalmanager.get("object_types_store").reload();
                pimcore.globalmanager.get("object_types_store_create").reload();

                var data = Ext.decode(response.responseText);
                if (data && data.success) {
                    this.openClass(data.id);
                }
            }.bind(this)
        });

        return true;

    },

    deleteClass: function (tree, record) {

        Ext.Msg.confirm(t('delete'), sprintf(t('delete_class_message'), record.data.text), function (btn) {
            if (btn == 'yes') {
                Ext.Ajax.request({
                    url: Routing.generate('pimcore_admin_dataobject_class_delete'),
                    method: 'DELETE',
                    params: {
                        id: record.data.id
                    },
                    success: function () {
                        // refresh the object tree
                        var tree = pimcore.globalmanager.get("layout_object_tree").tree;
                        tree.getStore().load({
                            node: tree.getRootNode()
                        });

                        // update object type store
                        pimcore.globalmanager.get("object_types_store").reload();
                        pimcore.globalmanager.get("object_types_store_create").reload();
                    }
                });

                this.getEditPanel().removeAll();
                record.remove();
            }
        }.bind(this));
    },

    activate: function () {
        Ext.getCmp("pimcore_panel_tabs").setActiveItem("pimcore_classes");
    }

});
