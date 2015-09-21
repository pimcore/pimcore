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

pimcore.registerNS("pimcore.settings.tagmanagement.item");
pimcore.settings.tagmanagement.item = Class.create({


    initialize: function (data, parentPanel) {
        this.parentPanel = parentPanel;
        this.data = data;
        this.currentIndex = 0;

        this.addLayout();

        if(this.data.items && this.data.items.length > 0) {
            for(var i=0; i<this.data.items.length; i++) {
                this.addItem(this.data.items[i]);
            }
        }
    },


    addLayout: function () {

        this.editpanel = new Ext.Panel({
            region: "center",
            bodyStyle: "padding: 20px;",
            autoScroll: true
        });

        var panelButtons = [];
        panelButtons.push({
            text: t("save"),
            iconCls: "pimcore_icon_apply",
            handler: this.save.bind(this)
        }); 

        this.itemContainer = new Ext.Panel({
            title: t("tags"),
            style: "margin: 20px 0 0 0;",
            tbar: [{
                iconCls: "pimcore_icon_add",
                handler: this.addItem.bind(this)
            }],
            border: false
        });

        var paramsFieldSetItems = [];

        for(var i = 0; i < 5; i++) {
            paramsFieldSetItems.push({
                xtype: "fieldset",
                layout: "hbox",
                style: "border-top: none !important",
                border: false,
                padding: 0,
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name" + i,
                    value: this.data.params[i]["name"]
                },{
                    xtype: "textfield",
                    margin: '0 0 0 20',
                    fieldLabel: t("value"),
                    name: "params.value" + i,
                    value: this.data.params[i]["value"]
                }]
            });
        }

        var paramsFieldSet = {
            xtype: "fieldset",
            title: t("parameters") + " (GET &amp; POST)",
            items: paramsFieldSetItems
        };



        this.panel = new Ext.form.FormPanel({
            border: false,
            closable: true,
            autoScroll: true,
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_tagmanagement_panel_" + this.data.name,
            labelWidth: 150,
            items: [{
                xtype: "textfield",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 450,
                disabled: true
            },{
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 450,
                height: 50
            },{
                xtype: "fieldset",
                title: t("conditions"),
                items: [{
                    xtype: "combo",
                    name: "siteId",
                    fieldLabel: t("site"),
                    store: pimcore.globalmanager.get("sites"),
                    valueField: "id",
                    displayField: "domain",
                    triggerAction: "all",
                    value: this.data.siteId
                },{
                    xtype: "textfield",
                    name: "urlPattern",
                    value: this.data.urlPattern,
                    fieldLabel: t("url_pattern"),
                    width: 550,
                    fieldCls: "input_drop_target",
                    listeners: {
                        "render": function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: el,
                                ddGroup: "element",
                                getTargetFromEvent: function(e) {
                                    return this.getEl();
                                }.bind(el),

                                onNodeOver : function(target, dd, e, data) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                },

                                onNodeDrop : function (el, target, dd, e, data) {
                                    try {
                                        var record = data.records[0];
                                        var data = record.data;

                                        if (data.elementType == "document") {
                                            var pattern = preg_quote(data.path);
                                            pattern = str_replace("@", "\\@", pattern);
                                            pattern = "@^" + pattern + "$@";
                                            el.setValue(pattern);
                                            return true;
                                        }
                                    } catch (e) {
                                        console.log(e);
                                    }
                                    return false;
                                }.bind(this, el)
                            });
                        }.bind(this)
                    }
                },{
                    xtype:'combo',
                    fieldLabel: t('http_method'),
                    name: "httpMethod",
                    store: [["",t("any")],["get","GET"],["post","POST"]],
                    triggerAction: "all",
                    typeAhead: false,
                    editable: false,
                    forceSelection: true,
                    mode: "local",
                    value: this.data.httpMethod,
                    width: 250
                },{
                    xtype: "textfield",
                    name: "textPattern",
                    value: this.data.textPattern,
                    fieldLabel: t("matching_text"),
                    width: 400
                },
                    paramsFieldSet
                ]
            }, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            border: true,
            tbar: ["->",{
                iconCls: "pimcore_icon_delete",
                handler: function (myId) {
                    this.itemContainer.remove(Ext.getCmp(myId));
                }.bind(this, myId)
            }],
            items: [{
                xtype: "textarea",
                width: 440,
                height: 200,
                fieldLabel: t("code"),
                name: "item." + myId + ".code",
                value: data.code
            },{
                xtype:'combo',
                fieldLabel: t('element_css_selector'),
                name: "item." + myId + ".element",
                disableKeyFilter: true,
                store: [["body","body"],["head","head"]],
                triggerAction: "all",
                mode: "local",
                value: data.element,
                width: 250
            },{
                xtype:'combo',
                fieldLabel: t('insert_position'),
                name: "item." + myId + ".position",
                store: [["beginning",t("beginning")],["end",t("end")]],
                triggerAction: "all",
                typeAhead: false,
                editable: false,
                forceSelection: true,
                mode: "local",
                value: data.position,
                width: 250
            }]
        });

        this.itemContainer.add(item);
        this.itemContainer.updateLayout();

        this.currentIndex++;
    },

    save: function () {

        var formValues = this.panel.getForm().getFieldValues();
        formValues.name = this.data.name;
        Ext.Ajax.request({
            url: "/admin/settings/tag-management-update",
            method: "post",
            params: {
                configuration: Ext.encode(formValues),
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getStore().load();
        pimcore.helpers.showNotification(t("success"), t("tag_saved_successfully"), "success");
    },

    getCurrentIndex: function () {
        return this.currentIndex;
    }

});
