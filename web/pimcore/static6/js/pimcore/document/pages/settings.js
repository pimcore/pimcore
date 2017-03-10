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

pimcore.registerNS("pimcore.document.pages.settings");
pimcore.document.pages.settings = Class.create(pimcore.document.settings_abstract, {

    getLayout: function () {

        if (this.layout == null) {

            // redirects
            var addUrlAlias = function (url, id) {

                if(typeof url != "string") {
                    url = "";
                }
                if(typeof id != "string" && typeof id != "number") {
                    id = "";
                }

                var count = this.urlAliasPanel.query("textfield").length+1;

                var compositeField = new Ext.Container({
                    hideLabel: true,
                    style: "padding-bottom:5px;",
                    items: [{
                        xtype: "textfield",
                        value: url,
                        width: 630,
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
                        this.urlAliasPanel.updateLayout();
                    }.bind(this, compositeField)
                },{
                    xtype: "box",
                    style: "clear:both;"
                }]);

                this.urlAliasPanel.add(compositeField);

                this.urlAliasPanel.updateLayout();
            }.bind(this);

            var user = pimcore.globalmanager.get("user");

            this.urlAliasPanel = new Ext.form.FieldSet({
                title: t("path_aliases") + " (" + t("redirects") + ")",
                collapsible: false,
                autoHeight: true,
                style: "margin-top: 0;",
                layout: 'fit',
                width: 700,
                disabled: !user.isAllowed("redirects"),
                items: [{
                    xtype: "toolbar",
                    style: "margin-bottom: 10px;",
                    items: ["->", {
                        text: t("add"),
                        iconCls: "pimcore_icon_add",
                        handler: addUrlAlias
                    }]
                }]
            });

            for(var r=0; r<this.document.data.redirects.length; r++) {
                addUrlAlias(this.document.data.redirects[r].source, this.document.data.redirects[r]["id"]);
            }

            // meta-data
            var addMetaData = function (value) {

                if(typeof value != "string") {
                    value = "";
                }

                var count = this.metaDataPanel.query("button").length+1;

                var compositeField = new Ext.form.FieldContainer({
                    layout: 'hbox',
                    hideLabel: true,
                    items: [{
                        xtype: "textfield",
                        value: value,
                        width: 636,
                        name: "metadata_" + count,
                    }]
                });

                compositeField.add({
                    xtype: "button",
                    iconCls: "pimcore_icon_delete",
                    handler: function (compositeField, el) {
                        this.metaDataPanel.remove(compositeField);
                        this.metaDataPanel.updateLayout();
                    }.bind(this, compositeField)
                });

                this.metaDataPanel.add(compositeField);
                this.metaDataPanel.updateLayout();
            }.bind(this);

            this.metaDataPanel = new Ext.form.FieldSet({
                title: t("meta_data"),
                collapsible: false,
                autoHeight:true,
                width: 700,
                style: "margin-top: 20px;",
                items: [{
                    xtype: "toolbar",
                    style: "margin-bottom: 10px;",
                    items: ["->", {
                        xtype: 'button',
                        iconCls: "pimcore_icon_add",
                        handler: addMetaData
                    }]
                }]
            });

            try {
                if(typeof this.document.data.metaData == "object" && this.document.data.metaData.length > 0) {
                    for(var r=0; r<this.document.data.metaData.length; r++) {
                        addMetaData(this.document.data.metaData[r]);
                    }
                }
            } catch (e) {}



            // create layout
            this.layout = new Ext.FormPanel({
                title: t('settings'),
                border: false,
                autoScroll: true,
                iconCls: "pimcore_icon_settings",
                bodyStyle:'padding:0 10px 0 10px;',
                items: [
                    {
                        xtype:'fieldset',
                        title: t('name_and_meta_data'),
                        collapsible: true,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 200
                        },

                        defaultType: 'textarea',
                        items :[
                            {
                                fieldLabel: t('title') + " (" + this.document.data.title.length + ")",
                                name: 'title',
                                maxLength: 255,
                                height: 51,
                                width: 700,
                                value: this.document.data.title,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.labelEl.update(t("title") + " (" + el.getValue().length + "):");
                                    }
                                }
                            },
                            {
                                fieldLabel: t('description') + " (" + this.document.data.description.length + ")",
                                maxLength: 255,
                                height: 51,
                                width: 700,
                                name: 'description',
                                value: this.document.data.description,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.labelEl.update(t("description") + " (" + el.getValue().length + "):");
                                    }
                                }
                            },
                            this.metaDataPanel
                        ]
                    },{
                        xtype:'fieldset',
                        title: t('pretty_url') + " / " + t("redirects"),
                        collapsible: true,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 300
                        },
                        defaultType: 'textfield',
                        items :[
                            {
                                fieldLabel: t('pretty_url_label'),
                                name: 'prettyUrl',
                                maxLength: 255,
                                width: 700,
                                value: this.document.data.prettyUrl,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        Ext.Ajax.request({
                                            url: "/admin/page/check-pretty-url",
                                            params: {
                                                id: this.document.id,
                                                path: el.getValue()
                                            },
                                            success: function (res) {
                                                res = Ext.decode(res.responseText);
                                                if(!res.success) {
                                                    el.getEl().addCls("pimcore_error_input");
                                                } else {
                                                    el.getEl().removeCls("pimcore_error_input");
                                                }
                                            }
                                        });
                                    }.bind(this)
                                }
                            }, this.urlAliasPanel
                        ]
                    }, {
                        xtype:'fieldset',
                        title: t('associate_target_group') + " (" + t("personas") + ")",
                        collapsible: true,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 300
                        },
                        defaultType: 'textfield',
                        items :[
                            Ext.create('Ext.ux.form.MultiSelect', {
                                fieldLabel: t('visitors_of_this_page_will_be_automatically_associated_with_the_selected_personas'),

                                store: pimcore.globalmanager.get("personas"),
                                displayField: "text",
                                valueField: "id",
                                name: 'personas',
                                width: 700,
                                //listWidth: 200,
                                value: this.document.data["personas"],
                                minHeight: 100
                            })
                        ]
                    },
                    this.getControllerViewFields(),
                    this.getPathAndKeyFields(),
                    this.getContentMasterFields()
                ]
            });
        }

        return this.layout;
    },

    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        // get values
        var settings = this.getLayout().getForm().getFieldValues();
        return settings;
    }

});