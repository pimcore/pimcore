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

pimcore.registerNS("pimcore.settings.user.workspace.language");

pimcore.settings.user.workspace.language = Class.create({

    initialize: function (type, data) {
        this.type = type;
        this.data = data;
    },

    getLayout: function() {
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
            width: 350,
            height: 480,
            value: this.data

        };

        this.box = new Ext.ux.form.MultiSelect(options);

        this.window = new Ext.Panel({
            xtype: "form",
            bodyStyle: "padding: 10px;",
            items: [this.box]
        });

        return this.window;
    },

    getValue: function() {
        var value = this.box.getValue();
        return value;
    },

    getType: function() {
        return this.type;
    }
});