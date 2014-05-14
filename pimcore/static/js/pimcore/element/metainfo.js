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

pimcore.registerNS("pimcore.element.metainfo");
pimcore.element.metainfo = Class.create({
    getClassName: function (){
        return "pimcore.element.metainfo";
    },

    initialize: function (data, elementType) {
        this.data = data;
        this.elementType = elementType;

        this.getInputWindow();
        this.detailWindow.show();
    },


    getInputWindow: function () {

        if(!this.detailWindow) {
            var height = this.data.length > 8 ? 400 : 300;
            this.detailWindow = new Ext.Window({
                width: 800,
                height: height,
                iconCls: "pimcore_icon_info",
                title: t('element_metainfo'),
                layout: "fit",
                closeAction:'close',
                plain: true,
                maximized: false,
                autoScroll: true,
                modal: true,
                buttons: [
                    {
                        text: t('close'),
                        iconCls: "pimcore_icon_empty",
                        handler: function(){
                            this.detailWindow.hide();
                            this.detailWindow.destroy();
                        }.bind(this)
                    }
                ]
            });

            this.createPanel();
        }
        return this.detailWindow;
    },


    createPanel: function() {
        var items = [];

        for (var i=0; i<this.data.length; i++) {

            var item;

            if(this.data[i]["type"] == "date") {
                item = {
                    xtype: "textfield",
                    fieldLabel: t(this.data[i]["name"]),
                    readOnly: true,
                    value: new Date(this.data[i]["value"] * 1000) + " (" + this.data[i]["value"] + ")",
                    width: 600
                };
            } else {
                var type = this.data[i]["type"];
                var value = this.data[i]["value"];
                var name = t(this.data[i]["name"]);
                if (type == "user") {

                    var htmlValue = value;

                    var user = pimcore.globalmanager.get("user");
                    if (user.admin) {
                        htmlValue = value + " " + '<a href="#">' + t("click_to_open") +  '</a>';
                    }

                    item = {
                        xtype: "label",
                        fieldLabel: name,
                        readOnly: true,
                        html: htmlValue,
                        width: 600
                    };
                    if (user.admin) {
                        item.listeners = {
                            render: function(value, detailWindow, c){
                                c.getEl().on('click', function(){
                                    pimcore.helpers.showUser(value);
                                    detailWindow.close();
                                }, c);
                            }.bind(this, value, this.detailWindow)
                        };
                    }

                } else {

                    item = {
                        xtype: "textfield",
                        fieldLabel: name,
                        readOnly: true,
                        value: value,
                        width: 600
                    };
                }
            }
            items.push(item);
        }

        var panel = new Ext.form.FormPanel({
            border: false,
            frame:false,
            bodyStyle: 'padding:10px',
            items: items,
            labelWidth: 130,
            collapsible: false,
            autoScroll: true
        });

        this.detailWindow.add(panel);
    }

});