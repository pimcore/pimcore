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

pimcore.registerNS("pimcore.document.newsletters.addressSourceAdapters.default");
pimcore.document.newsletters.addressSourceAdapters.default = Class.create({

    initialize: function(document, data) {
        this.document = document;
    },

    /**
     * returns name of corresponding php implementation class
     *
     * @returns {string}
     */
    getName: function() {
        return "defaultAdapter";
    },

    /**
     * returns layout for sending panel
     *
     * @returns {Ext.form.Panel|*}
     */
    getLayout: function () {

        if (this.layout == null) {

            this.layout = Ext.create('Ext.form.Panel', {
                border: false,
                autoScroll: true,
                defaults: {labelWidth: 200},
                items: [
                    {
                        xtype: "combo",
                        name: "class",
                        fieldLabel: t("class"),
                        triggerAction: 'all',
                        editable: false,
                        store: new Ext.data.Store({
                            autoDestroy: true,
                            proxy: {
                                type: 'ajax',
                                url: "/admin/newsletter/get-available-classes",
                                reader: {
                                    type: 'json',
                                    rootProperty: 'data'
                                }
                            },
                            fields: ["name"]
                        }),
                        width: 600,
                        displayField: 'name',
                        valueField: 'name'
                    },{
                        xtype: "textfield",
                        name: "objectFilterSQL",
                        value: "",
                        fieldLabel: t("object_filter") + " (SQL)",
                        width: 600,
                        itemId: "objectFilterSQL",
                        enableKeyEvents: true,
                        listeners: {
                            keyup: function (el) {

                                Ext.Ajax.request({
                                    url: "/admin/newsletter/checksql",
                                    params: this.layout.getForm().getFieldValues(),
                                    success: function (response) {
                                        var res = Ext.decode(response.responseText);

                                        if(!this.sqlTooltip) {
                                            this.sqlTooltip = new Ext.ToolTip({
                                                title: '',
                                                target: el.getEl(),
                                                anchor: 'left',
                                                html: '',
                                                width: 140,
                                                height: 50,
                                                autoHide: false,
                                                closable: false
                                            });
                                            this.sqlTooltip.show();
                                        }

                                        if(res.success) {
                                            this.sqlTooltip.setTitle("OK");
                                            this.sqlTooltip.update( res.count + " " + t("recipients"));
                                        } else {
                                            this.sqlTooltip.setTitle(t("error"));
                                            this.sqlTooltip.update(t("error"));
                                        }
                                    }.bind(this)
                                });
                            }.bind(this)
                        }
                    },{
                        fieldLabel: t('associate_target_group') + " (" + t("personas") + ")",
                        xtype: "multiselect",
                        hidden: pimcore.globalmanager.get("personas").getCount() < 1,
                        store: pimcore.globalmanager.get("personas"),
                        displayField: "text",
                        valueField: "id",
                        name: 'personas',
                        width: 600
                    }
                ]
            });
        }

        return this.layout;
    },

    /**
     * returns values for sending process
     *
     * @returns {*|Object}
     */
    getValues: function () {

        if (!this.layout.rendered) {
            throw "settings not available";
        }

        return this.getLayout().getForm().getFieldValues();
    }

});