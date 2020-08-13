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

pimcore.registerNS("pimcore.element.note_details");
pimcore.element.note_details = Class.create({
    getClassName: function (){
        return "pimcore.element.note_details";
    },

    initialize: function (data) {
        this.data = data;
        this.getInputWindow();
        this.detailWindow.show();
    },


    getInputWindow: function () {

        if(!this.detailWindow) {
            this.detailWindow = new Ext.Window({
                width: 700,
                height: 530,
                title: t('details'),
                closeAction:'close',
                plain: true,
                autoScroll: true,
                modal: true,
                buttons: [
                    {
                        text: t('close'),
                        iconCls: "pimcore_icon_cancel",
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

        items.push({
            xtype: "textfield",
            fieldLabel: t('type'),
            readOnly: true,
            value: this.data.type
        });

        items.push({
            xtype: "textfield",
            fieldLabel: t('title'),
            readOnly: true,
            value: this.data.title
        });

        items.push({
            xtype: "textarea",
            fieldLabel: t('description'),
            readOnly: true,
            value: this.data.description,
            height: 200
        });


        var v;
        if(this.data.data) {
            v =  this.data.data.length;
        } else {
            v = "";
        }

        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('fields'),
                readOnly: true,
                value: v
            }
        );

        var user;
        if(this.data.user && this.data.user["name"]) {
            user =  this.data.user["name"];
        } else {
            user = "";
        }




        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('user'),
                readOnly: true,
                value: user
            }
        );

        var date = new Date(this.data.date * 1000);

        items.push(
            {
                xtype: "textfield",
                fieldLabel: t('date'),
                readOnly: true,
                value: Ext.Date.format(date, "Y-m-d H:i:s")
            }
        );

        var panel = new Ext.form.FormPanel({
            border: false,
            frame:false,
            bodyStyle: 'padding:10px',
            items: items,
            collapsible: false,
            autoScroll: true,
            defaults: {
                labelWidth: 130,
                width: 650
            }
        });

        this.detailWindow.add(panel);
    }

});
