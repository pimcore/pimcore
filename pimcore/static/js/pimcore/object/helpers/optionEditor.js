/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2016 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
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


        var data = "";
        this.store.each(function (rec) {
                if (data.length > 0) {
                    data += "\n";
                }
                data += rec.get("key") + "," + rec.get("value");
            }
        );

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
            iconCls: "pimcore_icon_tab_edit",
            layout: "fit",
            closeAction:'close',
            plain: true,
            maximized: false,
            modal: true,
            buttons: [
                {
                    text: t('save'),
                    iconCls: "pimcore_icon_save",
                    handler: function(){

                        this.store.removeAll();

                        var lines = this.textarea.getValue().split('\n');
                        for(var i = 0;i < lines.length;i++){
                            line = lines[i];
                            var pair = lines[i].split(',');

                            var value = pair[1] ? pair[1] : pair[0];
                            var u = new this.store.recordType({
                                key: pair[0],
                                value: value
                            });
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
