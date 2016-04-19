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

pimcore.registerNS("pimcore.object.customviews.settings");
pimcore.object.customviews.settings = Class.create({

    initialize: function () {

        this.entryCount = 0;

        this.getTabPanel();
        this.loadData();
    },

    activate: function () {
        var tabPanel = Ext.getCmp("pimcore_panel_tabs");
        tabPanel.setActiveItem("pimcore_customviews");
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
                closable: true,
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
            tabPanel.setActiveItem("pimcore_customviews");

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

        var sorterField = new Ext.form.NumberField({
            fieldLabel: t("sort"),
            name: "sort_" + this.entryCount,
            width: 300,
            value: data.sort
        });

        var typedata = [
            ["asset", t("asset")],
            ["document", t("document")],
            ["object", t("object")]
        ];

        var treetype = data.treetype ? data.treetype : "object";

        var allowedClasses = new Ext.ux.form.MultiSelect({
            fieldLabel: t("allowed_classes"),
            name: "classes_" + this.entryCount + "[]",
            width: 500,
            height: 100,
            store: pimcore.globalmanager.get("object_types_store"),
            editable: false,
            value: data.classes,
            valueField: 'id',
            displayField: 'text',
            hidden: treetype != "object"
        });

        var typeCombo = new Ext.form.ComboBox({
            name: "treetype_" + this.entryCount,
            fieldLabel: t('tree_type'),
            width: 300,
            mode: 'local',
            autoSelect: true,
            editable: false,
            value: treetype,
            store: new Ext.data.ArrayStore({
                id: 0,
                fields: [
                    'id',
                    'label'
                ],
                data: typedata
            }),
            triggerAction: 'all',
            valueField: 'id',
            displayField: 'label',
            listeners: {
                select: function (field, fieldname) {
                    allowedClasses.setHidden(field.value != "object");
                }
            }
        });

        this.panel.add({
            xtype: 'panel',
            border: true,
            style: "margin-bottom: 10px",
            bodyStyle: "padding: 10px",
            id: "customviews_fieldset_" + this.entryCount,
            defaults: {
                labelWidth: 150
            },
            items: [
                typeCombo,
                {
                    xtype: "panel",
                    defaults: {
                        labelWidth: 150
                    },
                    items: [
                        {
                            xtype: "textfield",
                            fieldLabel: t("name"),
                            name: "name_" + this.entryCount,
                            width: 300,
                            value: data.name
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("icon"),
                            name: "icon_" + this.entryCount,
                            width: 500,
                            value: data.icon
                        },
                        {
                            xtype: "textfield",
                            fieldLabel: t("root_folder"),
                            name: "rootfolder_" + this.entryCount,
                            width: 500,
                            cls: "input_drop_target",
                            value: data.rootfolder,
                            listeners: {
                                "render": function (el) {
                                    new Ext.dd.DropZone(el.getEl(), {
                                        reference: this,
                                        ddGroup: "element",
                                        getTargetFromEvent: function (e) {
                                            return this.getEl();
                                        }.bind(el),

                                        onNodeOver: function (typeCombo, target, dd, e, data) {
                                            data = data.records[0].data;
                                            if (data.elementType == typeCombo.getValue()) {
                                                return Ext.dd.DropZone.prototype.dropAllowed;
                                            }
                                        }.bind(el, typeCombo),

                                        onNodeDrop: function (typeCombo, target, dd, e, data) {
                                            data = data.records[0].data;
                                            if (data.elementType == typeCombo.getValue()) {
                                                this.setValue(data.path);
                                                return true;
                                            }
                                            return false;
                                        }.bind(el, typeCombo)
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
                        allowedClasses,
                        {
                            xtype: "combobox",
                            fieldLabel: t("position"),
                            name: "position_" + this.entryCount,
                            width: 300,
                            value: data.position,
                            store: Ext.create('Ext.data.ArrayStore', {
                                fields: ['id', 'text'],
                                data: [["left", t("left")], ["right", t("right")]]
                            }),
                            valueField: "id",
                            displayField: "text"
                        },
                        sorterField,
                        {
                            xtype: "checkbox",
                            name: "expanded_" + this.entryCount,
                            checked: data.expanded,
                            fieldLabel: t("expanded")
                        }
                    ]
                }
            ],

            bbar: ['->',
                {
                    text: t("remove"),
                    iconCls: "pimcore_icon_delete",
                    handler: function (id) {
                        this.panel.remove("customviews_fieldset_" + id);
                        this.panel.updateLayout();
                    }.bind(this, this.entryCount)
                }
            ]
        });

        Ext.QuickTips.register({target: sorterField.getEl(), text: t("lower_sortvalues_first")});

        this.panel.updateLayout();

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
 