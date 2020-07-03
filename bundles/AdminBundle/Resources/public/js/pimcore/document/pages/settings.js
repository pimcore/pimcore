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

pimcore.registerNS("pimcore.document.pages.settings");
pimcore.document.pages.settings = Class.create(pimcore.document.settings_abstract, {

    getLayout: function () {

        if (this.layout == null) {
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
                title: t("html_tags") + " (&lt;meta .../&gt; &lt;link .../&gt; ...)",
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


            var updateSerpPreview = function () {

                var metaPanel = this.layout.getComponent("metaDataPanel");
                var title = metaPanel.getComponent("title").getValue();
                var description = metaPanel.getComponent("description").getValue();

                var truncate = function( text, n ){
                    if (text.length <= n) { return text; }
                    var subString = text.substr(0, n-1);
                    return subString.substr(0, subString.lastIndexOf(' ')) + " ...";
                };

                if(!title) {
                    metaPanel.getComponent("serpPreview").hide();
                    return false;
                }

                if(metaPanel.getEl().getWidth() > 1350) {
                    metaPanel.getComponent("serpPreview").show();
                }

                var desktopTitleEl = Ext.get(metaPanel.getComponent("serpPreview").getEl().selectNode(".desktop .title"));
                var stringParts = title.split(" ");
                desktopTitleEl.setHtml(title);
                while(desktopTitleEl.getWidth() >= 600) {
                    stringParts.splice(-1,1);
                    tmpString = stringParts.join(" ") + " ...";
                    desktopTitleEl.setHtml(tmpString);
                }

                var desktopDescrEl = metaPanel.getComponent("serpPreview").getEl().selectNode(".desktop .description");
                Ext.fly(desktopDescrEl).setHtml(truncate(description, 160));

                var mobileTitleEl = metaPanel.getComponent("serpPreview").getEl().selectNode(".mobile .title");
                Ext.fly(mobileTitleEl).setHtml(truncate(title, 78));

                var mobileDescrEl = metaPanel.getComponent("serpPreview").getEl().selectNode(".mobile .description");
                Ext.fly(mobileDescrEl).setHtml(truncate(description, 130));

                return true;
            }.bind(this);

            var serpAbsoluteUrl = this.document.data.url;

            // create layout
            this.layout = new Ext.FormPanel({
                title: t('SEO') + ' &amp; ' + t('settings'),
                border: false,
                autoScroll: true,
                iconCls: "pimcore_material_icon_page_settings pimcore_material_icon",
                bodyStyle:'padding:0 10px 0 10px;',
                items: [
                    {
                        xtype:'fieldset',
                        title: t('title') + ", " + t("description") + " & " + t('metadata'),
                        itemId: "metaDataPanel",
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
                                itemId: 'title',
                                maxLength: 255,
                                height: 51,
                                width: 700,
                                value: this.document.data.title,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.labelEl.update(t("title") + " (" + el.getValue().length + "):");
                                        updateSerpPreview();
                                    }
                                }
                            },
                            {
                                fieldLabel: t('description') + " (" + this.document.data.description.length + ")",
                                maxLength: 350,
                                height: 51,
                                width: 700,
                                name: 'description',
                                itemId: 'description',
                                value: this.document.data.description,
                                enableKeyEvents: true,
                                listeners: {
                                    "keyup": function (el) {
                                        el.labelEl.update(t("description") + " (" + el.getValue().length + "):");
                                        updateSerpPreview();
                                    }
                                }
                            },
                            this.metaDataPanel,
                            {
                                xtype: "container",
                                itemId: "serpPreview",
                                cls: "pimcore_document_page_serp_preview",
                                hidden: true,
                                html:
                                '<div class="entry desktop">' +
                                    '<div class="title"></div>' +
                                    '<div class="url">' + serpAbsoluteUrl + '</div>' +
                                    '<div class="description"></div>' +
                                '</div>' +
                                '<div class="entry mobile">' +
                                    '<div class="title"></div>' +
                                    '<div class="url">' + serpAbsoluteUrl + '</div>' +
                                    '<div class="description"></div>' +
                                '</div>'
                            }
                        ],
                        listeners: {
                            "afterrender": function (el) {
                                window.setTimeout(function () {
                                    if(updateSerpPreview() && el.getEl().getWidth() > 1350) {
                                        el.getComponent("serpPreview").show();
                                    }
                                }, 1000);
                            }
                        }
                    },{
                        xtype:'fieldset',
                        title: t('pretty_url') + " / URL Slug",
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
                                    "focusleave": function (el) {
                                        Ext.Ajax.request({
                                            url: Routing.generate('pimcore_admin_document_page_checkprettyurl'),
                                            method: "POST",
                                            params: {
                                                id: this.document.id,
                                                path: el.getValue()
                                            },
                                            success: function (res) {
                                                res = Ext.decode(res.responseText);
                                                if(!res.success) {
                                                    el.getEl().addCls("pimcore_error_input");
                                                    if (res.message) {
                                                        Ext.MessageBox.alert(t("info"), res.message);
                                                    }
                                                } else {
                                                    el.getEl().removeCls("pimcore_error_input");
                                                }
                                            }
                                        });
                                    }.bind(this)
                                }
                            }
                        ]
                    }, {
                        xtype:'fieldset',
                        title: t('assign_target_groups'),
                        collapsible: true,
                        autoHeight:true,
                        defaults: {
                            labelWidth: 300
                        },
                        defaultType: 'textfield',
                        items :[
                            Ext.create('Ext.ux.form.MultiSelect', {
                                fieldLabel: t('visitors_of_this_page_will_be_automatically_associated_with_the_selected_target_groups'),
                                store: pimcore.globalmanager.get("target_group_store"),
                                displayField: "text",
                                valueField: "id",
                                name: 'targetGroupIds',
                                width: 700,
                                //listWidth: 200,
                                value: this.document.data["targetGroupIds"],
                                minHeight: 100
                            })
                        ]
                    },
                    this.getControllerViewFields(true),
                    this.getPathAndKeyFields(true),
                    this.getContentMasterFields(true)
                ]
            });
        }

        return this.layout;
    }

});
