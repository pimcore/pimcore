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
 * @copyright  Copyright (c) 2009-2013 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     New BSD License
 */

pimcore.registerNS("pimcore.settings.user.workspace.language");

pimcore.settings.user.workspace.language = Class.create({

    initialize: function (callback, data, path) {
        this.callback = callback;
        this.data = data;
        this.path = path;
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
            hideLabel: true,
            width: 460,
            height: 390,
            value: this.data
        };

        this.box = new Ext.ux.form.MultiSelect(options);


        panelConfig.items.push({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            items: [this.box],
            title: this.path,
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