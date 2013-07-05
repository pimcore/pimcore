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
        var iconCls;
        if (this.elementType == "object") {
            iconCls = this.data.general.iconCls;
        } else {
            iconCls = "pimcore_icon_info_large";

        }

        var height;

        if (this.elementType == "object") {
            height = 500;
        } else {
            height = 450;
        }

        if(!this.detailWindow) {
            this.detailWindow = new Ext.Window({
                width: 600,
                height: height,
                iconCls: iconCls,
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

        var info;
        if (this.elementType == "object") {
            info = this.data.general;
        } else {
            info = this.data;
        }

        for (var key in info) {
            if (info.hasOwnProperty(key)) {
                if (typeof info[key] === "object") {
                    continue;
                }

                if (typeof info[key] === "string") {
                    if(key.substring(0,2) == "__") {
                        continue;
                    }
                }

                items.push({
                    xtype: "textfield",
                    fieldLabel: key,
                    readOnly: true,
                    value: info[key],
                    width: 400
                });

                if (key.indexOf("Date") !== -1) {
                    items.push({
                        xtype: "textfield",
                        fieldLabel: "",
                        readOnly: true,
                        value: new Date(info[key] * 1000),
                        width: 400
                    });
                }
            }


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