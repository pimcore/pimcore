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

        this.panel = new Ext.form.FormPanel({
            border: false,
            layout: "fit",
            closable: true,
            autoScroll: true,
            layout: "pimcoreform",
            bodyStyle: "padding: 20px;",
            title: this.data.name,
            id: "pimcore_tagmanagement_panel_" + this.data.name,
            labelWidth: 150,
            items: [{
                xtype: "textfield",
                name: "name",
                value: this.data.name,
                fieldLabel: t("name"),
                width: 300,
                disabled: true
            },{
                xtype: "textarea",
                name: "description",
                value: this.data.description,
                fieldLabel: t("description"),
                width: 300,
                height: 50
            },{
                xtype: "textfield",
                name: "urlPattern",
                value: this.data.urlPattern,
                fieldLabel: t("url_pattern"),
                width: 400
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
            }, {
                xtype: "displayfield",
                value: t("parameters") + " (GET &amp; POST)",
                hideLabel: true,
                style: "margin-top: 10px;"
            }, {
                xtype: "compositefield",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name0",
                    value: this.data.params[0]["name"]
                },{
                    xtype: "textfield",
                    fieldLabel: t("value"),
                    name: "params.value0",
                    value: this.data.params[0]["value"]
                }]
            }, {
                xtype: "compositefield",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name1",
                    value: this.data.params[1]["name"]
                },{
                    xtype: "textfield",
                    fieldLabel: t("value"),
                    name: "params.value1",
                    value: this.data.params[1]["value"]
                }]
            }, {
                xtype: "compositefield",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name2",
                    value: this.data.params[2]["name"]
                },{
                    xtype: "textfield",
                    fieldLabel: t("value"),
                    name: "params.value2",
                    value: this.data.params[2]["value"]
                }]
            }, {
                xtype: "compositefield",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name3",
                    value: this.data.params[3]["name"]
                },{
                    xtype: "textfield",
                    fieldLabel: t("value"),
                    name: "params.value3",
                    value: this.data.params[3]["value"]
                }]
            }, {
                xtype: "compositefield",
                items: [{
                    xtype: "textfield",
                    fieldLabel: t("name"),
                    name: "params.name4",
                    value: this.data.params[4]["name"]
                },{
                    xtype: "textfield",
                    fieldLabel: t("value"),
                    name: "params.value4",
                    value: this.data.params[4]["value"]
                }]
            }, this.itemContainer],
            buttons: panelButtons
        });


        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().activate(this.panel);

        pimcore.layout.refresh();
    },


    addItem: function (data) {

        if(typeof data == "undefined") {
            data = {};
        }
        var myId = Ext.id();

        var item =  new Ext.Panel({
            layout: "pimcoreform",
            id: myId,
            style: "margin: 10px 0 0 0",
            bodyStyle: "padding: 10px;",
            tbar: ["->",{
                iconCls: "pimcore_icon_delete",
                handler: function (myId) {
                    this.itemContainer.remove(Ext.getCmp(myId));
                }.bind(this, myId)
            }],
            items: [{
                xtype: "textarea",
                width: 500,
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
        this.itemContainer.doLayout();

        this.currentIndex++;
    },

    save: function () {

        var m = Ext.encode(this.panel.getForm().getFieldValues());
        Ext.Ajax.request({
            url: "/admin/settings/tag-management-update",
            method: "post",
            params: {
                configuration: m,
                name: this.data.name
            },
            success: this.saveOnComplete.bind(this)
        });
    },

    saveOnComplete: function () {
        this.parentPanel.tree.getRootNode().reload();
        pimcore.helpers.showNotification(t("success"), t("tag_saved_successfully"), "success");
    },

    getCurrentIndex: function () {
        return this.currentIndex;
    }

});
