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


/**
 * NOTE: This helper-methods are added to the classes pimcore.object.edit, pimcore.object.fieldcollection,
 * pimcore.object.tags.localizedfields
 */

pimcore.registerNS("pimcore.object.helpers.optionEditor");
pimcore.object.helpers.optionEditor = Class.create({

    initialize: function (store) {
        this.store = store;
    },

    edit: function() {

        var displayField = {
            xtype: "displayfield",
            region: "north",
            hideLabel: true,
            value: t('csv_seperated_options_info')
        };


        var data = [];
        this.store.each(function (rec) {
                data.push([rec.get("key"), rec.get("value")]);
            }
        );

        data = Ext.util.CSV.encode(data);

        this.textarea = new Ext.form.TextArea({
            region: "center",
            value: data
        });

        this.configPanel = new Ext.Panel({
            layout: "border",
            padding: 20,
            items: [displayField, this.textarea]
        });


        this.window = new Ext.Window({
            width: 800,
            height: 500,
            title: t('csv_seperated_options'),
            iconCls: "pimcore_icon_edit",
            layout: "fit",
            closeAction:'close',
            plain: true,
            maximized: false,
            modal: true,
            buttons: [
                {
                    text: t('apply'),
                    iconCls: "pimcore_icon_save",
                    handler: function(){
                        this.store.removeAll();
                        var content = this.textarea.getValue();
                        var csvData = Ext.util.CSV.decode(content);

                        for(var i = 0;i < csvData.length;i++){
                            var pair = csvData[i];
                            var key = pair[0];
                            var value = pair[1];

                            var u = {
                                key: key,
                                value: value
                            };
                            this.store.add(u);
                        }

                        this.window.hide();
                        this.window.destroy();
                    }.bind(this)
                },
                {
                    text: t('cancel'),
                    iconCls: "pimcore_icon_empty",
                    handler: function(){
                        this.window.hide();
                        this.window.destroy();
                    }.bind(this)
                }
            ]
        });

        this.window.add(this.configPanel);
        this.window.show();
    }
});
