/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Commercial License (PCL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PCL
 */

pimcore.registerNS("pimcore.document.pages.settings");
/**
 * @private
 */
pimcore.document.pages.settings = Class.create(pimcore.document.settings_abstract, {

    getLayout: function () {

        if (this.layout == null) {

            var updateSerpPreview = function () {

                var metaPanel = this.layout.getComponent("metaDataPanel");
                var title = htmlspecialchars(metaPanel.getComponent("title").getValue());
                var description = htmlspecialchars(metaPanel.getComponent("description").getValue());

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
                        title: t('title') + " & " + t("description"),
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
                                                path: pimcore.helpers.sanitizeUrlSlug(el.getValue())
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
                                    }.bind(this),
                                    "change": function (el) {
                                        const sanitizedValue = pimcore.helpers.sanitizeUrlSlug(el.getValue());
                                        el.setValue(sanitizedValue);
                                    }.bind(this)
                                }
                            }
                        ]
                    },
                ]
            });

            // To add additional block to settings
            const additionalSettings = new CustomEvent(pimcore.events.prepareDocumentPageSettingsLayout, {
                detail: {
                   layout: this.layout,
                   document: this.document
                }
            });
            document.dispatchEvent(additionalSettings);

            this.layout.add(this.getControllerViewFields(true));
            this.layout.add(this.getStaticGeneratorFields(true));
            this.layout.add(this.getPathAndKeyFields(true));
            this.layout.add(this.getContentMainFields(true));
        }

        return this.layout;
    }



});
