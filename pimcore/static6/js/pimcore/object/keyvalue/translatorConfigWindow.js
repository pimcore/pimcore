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

pimcore.registerNS("pimcore.object.keyvalue.translatorconfigwindow");
pimcore.object.keyvalue.translatorconfigwindow = Class.create({

    initialize: function (keyid, parentPanel, groupId) {
        this.parentPanel = parentPanel;
        this.keyid = keyid;
        this.groupId = groupId;
    },


    show: function() {
        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:310,
            autoScroll: true,
            closeAction:'close',
            modal: true
        });

        this.window.show();

        Ext.Ajax.request({
            url: "/admin/key-value/get-translator-configs",
            success: this.selectTranslator.bind(this),
            failure: function() {
                this.window.hide();
            }.bind(this)
        });
    },

    selectTranslator: function (response) {
        var availableTranslators = Ext.decode(response.responseText);

        var panelConfig = {
            items: []
        };

        var storeConfigs = new Ext.data.Store({
            autoDestroy: true,
            proxy: {
                type: 'memory',
                reader: {
                    type: 'json',
                    rootProperty: 'configurations'
                }
            },
            data: availableTranslators,
            fields: ["id","name","translator"]
        });

        panelConfig.items.push({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            title: t('keyvalue_translators'),
            items: [
                {
                    xtype: "combo",
                    fieldLabel: t('keyvalue_select_translator'),
                    name: "translator",
                    id: "translator",
                    mode: "local",
                    width: 250,
                    store: storeConfigs,
                    triggerAction: "all",
                    displayField: "name",
                    valueField: "id",
                    value: this.groupId
                }
            ],
            bbar: ["->",
                {
                    xtype: "button",
                    iconCls: "pimcore_icon_apply",
                    text: t('apply'),
                    handler: this.applyData.bind(this)
                }
            ]
        });

        this.window.add(new Ext.Panel(panelConfig));
        this.window.updateLayout();
    },


    applyData: function() {
        var value = Ext.getCmp("translator").getValue();
        this.parentPanel.applyTranslatorConfig(this.keyid, value);
        this.window.close();
    }
});