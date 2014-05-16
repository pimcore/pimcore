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
//            title: t('select_keyvalue_translator'),
            items: []
        };

        var storeConfigs = new Ext.data.JsonStore({
            autoDestroy: true,
            root: 'configurations',
            data: availableTranslators,
            idProperty: 'id',
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
        this.window.doLayout();
    },


    applyData: function() {
        var value = Ext.getCmp("translator").getValue();
        this.parentPanel.applyTranslatorConfig(this.keyid, value);
        this.window.close();
    }
});