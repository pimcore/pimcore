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
 * @copyright  Copyright (c) 2009-2014 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.object.customviews.settings");
pimcore.object.customviews.settings = Class.create({

    initialize: function() {

        this.entryCount = 0;

        this.getTabPanel();
        this.loadData();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.activate("pimcore_customviews");
    },

    getTabPanel: function () {

        if (!this.panel) {
            this.panel = new Ext.form.FormPanel({
                id: "pimcore_customviews",
                title: t("custom_views"),
                bodyStyle: "padding: 10px;",
                autoScroll: true,
                iconCls: "pimcore_icon_custom_views",
                border: false,
                closable:true,
                buttons: [
                    {
                        text: t("add"),
                        handler: this.add.bind(this),
                        iconCls: "pimcore_icon_add"
                    },
                    {
                        text: t("save"),
                        handler: this.save.bind(this),
                        iconCls: "pimcore_icon_apply"
                    }
                ]
            });

            var tabPanel = Ext.getCmp("pimcore_panel_tabs");
            tabPanel.add(this.panel);
            tabPanel.activate("pimcore_customviews");


            this.panel.on("destroy", function () {
                pimcore.globalmanager.remove("customviews");
            }.bind(this));

            pimcore.layout.refresh();
        }

        return this.panel;
    },

    loadData: function () {
        Ext.Ajax.request({
            url: "/admin/object-helper/get-customviews",
            success: this.loadDataComplete.bind(this)
        });
    },

    loadDataComplete: function (response) {
        
        var rdata = Ext.decode(response.responseText);
        if (rdata) {
            if (rdata.success) {
                var data = rdata.data;
                if (data.length > 0) {
                    for (var i = 0; i < data.length; i++) {
                        this.add(data[i]);
                    }
                }
            }
        }
    },

    add: function (data) {

        if (!data) {
            data = {
                name: "",
                condition: "",
                icon: "",
                showroot: false,
                rootfolder: ""
            };
        }

        this.panel.add({
            xtype: "fieldset",
            id: "customviews_fieldset_" + this.entryCount,
            items: [
                {
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "name_" + this.entryCount,
                    width: 200,
                    value: data.name
                },
                {
                    xtype: "textfield",
                    fieldLabel: t("icon"),
                    name: "icon_" + this.entryCount,
                    width: 400,
                    value: data.icon
                }/*,{
                 xtype: "textarea",
                 fieldLabel: t("condition"),
                 name: "condition_" + this.entryCount,
                 width: 400,
                 value: data.condition
                 }*/,
                {
                    xtype: "textfield",
                    fieldLabel: t("root_folder"),
                    name: "rootfolder_" + this.entryCount,
                    width: 400,
                    cls: "input_drop_target",
                    value: data.rootfolder,
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (target, dd, e, data) {
                                    if (data.node.attributes.elementType == "object") {
                                        this.setValue(data.node.attributes.path);
                                        return true;
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                },
                {
                    xtype: "checkbox",
                    name: "showroot_" + this.entryCount,
                    checked: data.showroot,
                    fieldLabel: t("show_root_node")
                },
                {
                    xtype: "multiselect",
                    fieldLabel: t("allowed_classes"),
                    name: "classes_" + this.entryCount,
                    width: 'auto',
                    store: pimcore.globalmanager.get("object_types_store"),
                    editable: false,
                    value: data.classes,
                    valueField: 'id',
                    displayField: 'text'
                }
            ],
            buttons: [
                {
                    text: t("remove"),
                    iconCls: "pimcore_icon_delete",
                    handler: function (id) {
                        this.panel.remove("customviews_fieldset_" + id);
                        this.panel.doLayout();
                    }.bind(this, this.entryCount)
                }
            ]
        });

        this.panel.doLayout();

        this.entryCount++;
    },

    save: function () {

        var values = this.panel.getForm().getFieldValues();

        Ext.Ajax.request({
            url: "/admin/object-helper/save-customviews",
            params: values,
            success: function (response) {
                Ext.MessageBox.confirm(t("info"), t("reload_pimcore_changes"), function (buttonValue) {
                    if (buttonValue == "yes") {
                        window.location.reload();
                    }
                });
            }
        });
    }

});
 