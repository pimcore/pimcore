pimcore.registerNS("pimcore.settings.user.workspace.language");

pimcore.settings.user.workspace.language = Class.create({

    initialize: function (callback, data) {
        this.callback = callback;
        this.data = data;
    },

    show: function() {
        this.window = new Ext.Window({
            layout:'fit',
            width:500,
            height:500,
            autoScroll: true,
            closeAction:'close',
            modal: true
        });


        var panelConfig = {
            items: []
        };

        var data = [];
        var nrOfLanguages = pimcore.settings.websiteLanguages.length;
        for (var i = 0; i < nrOfLanguages; i++) {
            var language = pimcore.settings.websiteLanguages[i];
            data.push([language, pimcore.available_languages[language]]);
        }


        var options = {
            name: "languages",
            triggerAction: "all",
            editable: false,
            store: data,
//            itemCls: "object_field",
            width: 300,
            height: 300,
            value: this.data
        };

        this.box = new Ext.ux.form.MultiSelect(options);


        panelConfig.items.push({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            items: [this.box],
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
        this.window.show();
    },

    applyData: function() {
        var value = this.box.getValue();
        this.window.close();
        this.callback(value);
    }
});