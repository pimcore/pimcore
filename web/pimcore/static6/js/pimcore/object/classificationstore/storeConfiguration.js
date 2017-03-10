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

pimcore.registerNS("pimcore.object.classificationstore.storeConfiguration");
pimcore.object.classificationstore.storeConfiguration = Class.create({

    initialize: function (storeConfig, callback) {
        if (storeConfig) {
            this.storeConfig = storeConfig;
        } else {
            this.storeConfig = {};
        }

        this.callback = callback;
    },


    show: function() {


        this.formPanel = new Ext.form.FormPanel({
            border: false,
            frame:false,
            bodyStyle: 'padding:10px',
            items: [
                {
                    xtype: 'textfield',
                    name: 'name',
                    fieldLabel: t('name'),
                    value: this.storeConfig.name
                },
                {
                    xtype: 'textfield',
                    fieldLabel: t('description'),
                    name: 'description',
                    value: this.storeConfig.description,
                }
            ],
            defaults: {
                labelWidth: 130,
                width: 500
            },
            collapsible: false,
            autoScroll: true
        });

        this.window = new Ext.Window({
            modal: true,
            width: 600,
            height: 250,
            resizable: true,
            autoScroll: true,
            title: t("classificationstore_detailed_config"),
            items: [this.formPanel],
            bbar: [
            "->",{
                xtype: "button",
                text: t("cancel"),
                iconCls: "pimcore_icon_cancel",
                handler: function () {
                    this.window.close();
                }.bind(this)
            },{
                xtype: "button",
                text: t("apply"),
                iconCls: "pimcore_icon_apply",
                handler: function () {
                    this.applyData();
                }.bind(this)
            }],
            plain: true
        });

        this.window.show();
    },

    applyData: function() {

        this.callback(this.storeConfig.id, this.formPanel.getValues());
        this.window.close();
    }

});