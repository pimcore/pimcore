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

pimcore.registerNS("pimcore.document.pages.settings");
pimcore.document.pages.settings = Class.create({

    initialize: function(page) {
        this.page = page;
    },


    getLayout: function () {

        if (this.layout == null) {

            var docTypeStore = new Ext.data.JsonStore({
                url: '/admin/document/get-doc-types?type=page',
                fields: ["id","name","module","controller","action","template"],
                root: "docTypes"
            });

            // redirects
            var addUrlAlias = function (url, id) {

                if(typeof url != "string") {
                    url = "";
                }
                if(typeof id != "string" && typeof id != "number") {
                    id = "";
                }

                var count = this.urlAliasPanel.findByType("textfield").length+1;

                var compositeField = new Ext.Container({
                    hideLabel: true,
                    style: "padding-bottom:5px;",
                    items: [{
                        xtype: "textfield",
                        value: url,
                        width: 500,
                        name: "redirect_url_" + count,
                        style: "float:left;margin-right:5px;",
                        enableKeyEvents: true,
                        listeners: {
                            keyup: function () {
                                if(this.getValue().indexOf("http") >= 0) {
                                    try {
                                        var newUrl = "@" + preg_quote(parse_url(this.getValue(), "path")) + "@";
                                        this.setValue(newUrl);
                                    } catch (e) {
                                        console.log(e);
                                    }
                                }
                            }
                        }
                    },{
                        xtype: "hidden",
                        value: id,
                        name: "redirect_id_"  + count
                    }]
                });

                compositeField.add([{
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    style: "float:left;",
                    handler: function (compositeField, el) {
                        this.urlAliasPanel.remove(compositeField);
                        this.urlAliasPanel.doLayout();
                    }.bind(this, compositeField)
                },{
                    xtype: "box",
                    style: "clear:both;"
                }]);


                this.urlAliasPanel.add(compositeField);

                this.urlAliasPanel.doLayout();
            }.bind(this);

            var user = pimcore.globalmanager.get("user");

            this.urlAliasPanel = new Ext.form.FieldSet({
                title: t("path_aliases") + " (" + t("redirects") + ")",
                collapsible: false,
                autoHeight:true,
                width: 700,
                style: "margin-top: 20px;",
                disabled: !user.isAllowed("redirects"),
                items: [],
                buttons: [{
                    text: t("add"),
                    iconCls: "pimcore_icon_add",
                    handler: addUrlAlias
                }]
            });

            for(var r=0; r<this.page.data.redirects.length; r++) {
                addUrlAlias(this.page.data.redirects[r].source, this.page.data.redirects[r]["id"]);
            }



            // meta-data
            var addMetaData = function (idName, idValue, contentName, contentValue) {

                if(typeof idName != "string") {
                    idName = "";
                }
                if(typeof idValue != "string") {
                    idValue = "";
                }
                if(typeof contentName != "string") {
                    contentName = "";
                }
                if(typeof contentValue != "string") {
                    contentValue = "";
                }

                var count = this.metaDataPanel.findByType("button").length+1;

                var combolisteners = {
                    "afterrender": function (el) {
                        el.getEl().parent().applyStyles({
                            float: "left",
                            "margin-right": "5px"
                        });
                    }
                };

                var compositeField = new Ext.Container({
                    hideLabel: true,
                    style: "padding-bottom:5px;",
                    items: [{
                        xtype: "label",
                        text: "<meta ",
                        style: "float:left;margin-right:5px;"
                    },{
                        xtype: "combo",
                        store: ["name","property"],
                        typeAhead: false,
                        forceSelection: true,
                        triggerAction: "all",
                        value: idName,
                        mode: "local",
                        width: 120,
                        name: "metadata_idName_" + count,
                        listeners: combolisteners
                    },{
                        xtype: "label",
                        text: ' = ',
                        style: "float:left;margin-right:5px;"
                    },{
                        xtype: "combo",
                        store: ["","og:title","og:type","og:url","og:image","og:description","og:locale",
                                                                "twitter:card","twitter:site","twitter:creator"],
                        value: idValue,
                        editable: true,
                        mode: "local",
                        triggerAction: "all",
                        width: 120,
                        name: "metadata_idValue_" + count,
                        listeners: combolisteners
                    },{
                        xtype: "combo",
                        store: ["content"],
                        typeAhead: false,
                        forceSelection: true,
                        triggerAction: "all",
                        value: "content",
                        editable: true,
                        mode: "local",
                        width: 120,
                        name: "metadata_contentName_" + count,
                        listeners: combolisteners
                    },{
                        xtype: "label",
                        text: ' = ',
                        style: "float:left;margin-right:5px;"
                    },{
                        xtype: "textfield",
                        value: contentValue,
                        width: 140,
                        name: "metadata_contentValue_" + count,
                        style: "float:left;margin-right:5px;"
                    },{
                        xtype: "label",
                        text: ' />',
                        style: "float:left;margin-right:5px;"
                    }]
                });

                compositeField.add([{
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    style: "float:left;",
                    handler: function (compositeField, el) {
                        this.metaDataPanel.remove(compositeField);
                        this.metaDataPanel.doLayout();
                    }.bind(this, compositeField)
                },{
                    xtype: "box",
                    style: "clear:both;"
                }]);


                this.metaDataPanel.add(compositeField);
                this.metaDataPanel.doLayout();
            }.bind(this);

            this.metaDataPanel = new Ext.form.FieldSet({
                title: t("meta_data"),
                collapsible: false,
                autoHeight:true,
                width: 700,
                style: "margin-top: 20px;",
                items: [],
                buttons: [{
                    text: t("add"),
                    iconCls: "pimcore_icon_add",
                    handler: addMetaData
                }]
            });

            try {
                if(typeof this.page.data.metaData == "object" && this.page.data.metaData.length > 0) {
                    for(var r=0; r<this.page.data.metaData.length; r++) {
                        addMetaData(this.page.data.metaData[r]["idName"], this.page.data.metaData[r]["idValue"],
                            this.page.data.metaData[r]["contentName"], this.page.data.metaData[r]["contentValue"]);
                    }
                }
            } catch (e) {}



            // create layout
            this.layout = new Ext.FormPanel({
                title: t('settings'),
                bodyStyle:'padding:20px 5px 20px 5px;',
                layout: "pimcoreform",
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_tab_settings",
                items: [
                    {
                        xtype:'fieldset',
                        title: t('name_and_meta_data'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textarea',
                        items :[
                            {
                                fieldLabel: t('title') + " (" + this.page.data.title.length + ")",
                                name: 'title',
                                maxLength: 255,
                                height: 51,
                                width: 500,
                                value: this.page.data.title,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.label.update(t("title") + " (" + el.getValue().length + "):");
                                    }
                                }
                            },
                            {
                                fieldLabel: t('description') + " (" + this.page.data.description.length + ")",
                                maxLength: 255,
                                height: 51,
                                width: 500,
                                name: 'description',
                                value: this.page.data.description,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.label.update(t("description") + " (" + el.getValue().length + "):");
                                    }
                                }
                            },
                            {
                                fieldLabel: t('keywords')  + " (" + this.page.data.keywords.length + ")",
                                name: 'keywords',
                                maxLength: 255,
                                height: 51,
                                width: 500,
                                value: this.page.data.keywords,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.label.update(t("keywords") + " (" + el.getValue().length + "):");
                                    }
                                }
                            },
                            this.metaDataPanel
                        ]
                    },{
                        xtype:'fieldset',
                        title: t('associate_target_group') + " (" + t("personas") + ")",
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 320,
                        //hidden: pimcore.globalmanager.get("personas").getCount() < 1, // this kills getValue() method
                        defaultType: 'textfield',
                        items :[
                            {
                                fieldLabel: t('visitors_of_this_page_will_be_automatically_associated_with_the_selected_personas'),
                                xtype: "multiselect",
                                store: pimcore.globalmanager.get("personas"),
                                displayField: "text",
                                valueField: "id",
                                name: 'personas',
                                width: 200,
                                value: this.page.data["personas"]
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('pretty_url') + " / " + t("redirects"),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 300,
                        defaultType: 'textfield',
                        items :[
                            {
                                fieldLabel: t('pretty_url_label'),
                                name: 'prettyUrl',
                                maxLength: 255,
                                width: 400,
                                value: this.page.data.prettyUrl,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        Ext.Ajax.request({
                                            url: "/admin/page/check-pretty-url",
                                            params: {
                                                id: this.page.id,
                                                path: el.getValue()
                                            },
                                            success: function (res) {
                                                res = Ext.decode(res.responseText);
                                                if(!res.success) {
                                                    el.getEl().addClass("pimcore_error_input");
                                                } else {
                                                    el.getEl().removeClass("pimcore_error_input");
                                                }
                                            }
                                        });
                                    }.bind(this)
                                }
                            }, this.urlAliasPanel
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('controller_and_view_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 150},
                        items :[
                            {
                                fieldLabel: t('predefined_document_type'),
                                name: 'docType',
                                xtype: "combo",
                                displayField:'name',
                                valueField: "id",
                                store: docTypeStore,
                                editable: false,
                                triggerAction: 'all',
                                width: 400,
                                listWidth: 400,
                                listeners: {
                                    "select": this.setDocumentType.bind(this)
                                }
                            },
                            {
                                fieldLabel: t('module_optional'),
                                name: 'module',
                                value: this.page.data.module
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('controller'),
                                displayField: 'name',
                                valueField: 'name',
                                name: "controller",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/misc/get-available-controllers",
                                    root: "data",
                                    fields: ["name"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                id: "pimcore_document_settings_controller_" + this.page.id,
                                value: this.page.data.controller,
                                width: 250,
                                listeners: {
                                    afterrender: function (el) {
                                        el.getStore().load();
                                    }
                                }
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('action'),
                                displayField: 'name',
                                valueField: 'name',
                                name: "action",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/misc/get-available-actions",
                                    root: "data",
                                    fields: ["name"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                value: this.page.data.action,
                                width: 250,
                                listeners: {
                                    "focus": function (el) {
                                        el.getStore().reload({
                                            params: {
                                                controllerName: Ext.getCmp("pimcore_document_settings_controller_"
                                                                                    + this.page.id).getValue()
                                            }
                                        });
                                    }.bind(this)
                                }
                            },
                            {
                                xtype:'combo',
                                fieldLabel: t('template'),
                                displayField: 'path',
                                valueField: 'path',
                                name: "template",
                                disableKeyFilter: true,
                                store: new Ext.data.JsonStore({
                                    autoDestroy: true,
                                    url: "/admin/misc/get-available-templates",
                                    root: "data",
                                    fields: ["path"]
                                }),
                                triggerAction: "all",
                                mode: "local",
                                value: this.page.data.template,
                                width: 250,
                                listeners: {
                                    afterrender: function (el) {
                                        el.getStore().load();
                                    }
                                }
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('path_and_key_settings'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items :[
                            {
                                fieldLabel: t('path'),
                                name: 'path',
                                value: this.page.data.path,
                                disabled: true
                            },
                            {
                                fieldLabel: t('key'),
                                name: 'key',
                                value: this.page.data.key,
                                disabled: true
                            },
                            {
                                fieldLabel: t('id'),
                                name: 'id',
                                value: this.page.data.id,
                                disabled: true
                            }
                        ]
                    },
                    {
                        xtype:'fieldset',
                        title: t('content_master_document'),
                        collapsible: true,
                        autoHeight:true,
                        labelWidth: 200,
                        defaultType: 'textfield',
                        defaults: {width: 400},
                        items :[
                            {
                                fieldLabel: t("document"),
                                name: "contentMasterDocumentPath",
                                value: this.page.data.contentMasterDocumentPath,
                                cls: "input_drop_target",
                                id: "contentMasterDocumentPath_" + this.page.id,
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
                                                if (data.node.attributes.elementType == "document") {
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
                                xtype: "toolbar",
                                width: 605,
                                items: [{
                                    text:t("apply_new_master_document"),
                                    iconCls:"pimcore_icon_apply",
                                    autoWidth:true,
                                    handler:function () {
                                        Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"),
                                            function (buttonValue) {
                                                if (buttonValue == "yes") {
                                                    Ext.Ajax.request({
                                                        url:"/admin/page/change-master-document/id/" + this.page.id,
                                                        params:{
                                                            contentMasterDocumentPath:Ext.getCmp(
                                                                "contentMasterDocumentPath_" + this.page.id).getValue()
                                                        },
                                                        success:function () {
                                                            this.page.reload();
                                                        }.bind(this)
                                                    });
                                                }
                                            }.bind(this));
                                    }.bind(this)
                                },{
                                    text:t("delete_master_document"),
                                    iconCls:"pimcore_icon_delete",
                                    autoWidth:true,
                                    handler:function () {
                                        Ext.MessageBox.confirm(t("are_you_sure"), t("all_content_will_be_lost"),
                                            function (buttonValue) {
                                                if (buttonValue == "yes") {
                                                    Ext.getCmp("contentMasterDocumentPath_"
                                                                                    + this.page.id).setValue("");
                                                    Ext.Ajax.request({
                                                        url:"/admin/page/change-master-document/id/"
                                                                                    + this.page.id,
                                                        params:{
                                                            contentMasterDocumentPath:""
                                                        },
                                                        success:function () {
                                                            this.page.reload();
                                                        }.bind(this)
                                                    });
                                                }
                                            }.bind(this));
                                    }.bind(this)
                                }, {
                                    text: t("open_master_document"),
                                    iconCls: "pimcore_icon_edit",
                                    autoWidth: true,
                                    handler: function () {
                                        var masterPath = Ext.getCmp("contentMasterDocumentPath_" + this.page.id).getValue();
                                        pimcore.helpers.openDocumentByPath(masterPath);
                                    }.bind(this)
                                }]
                            }
                        ]
                    }
                ]
            });
        }

        return this.layout;
    },

    setDocumentType: function (field, newValue, oldValue) {
        var allowedFields = ["module","controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;

        for (var i = 0; i < allowedFields.length; i++) {
            element = form.findField(allowedFields[i]);
            if (element) {
                if (newValue.data.id > 0) {
                    element.setValue(newValue.data[allowedFields[i]]);
                }
            }
        }
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        var fields = ["module","controller","action","template"];
        var form = this.getLayout().getForm();
        var element = null;


        // get values
        var settings = this.getLayout().getForm().getFieldValues();
        return settings;
    }

});