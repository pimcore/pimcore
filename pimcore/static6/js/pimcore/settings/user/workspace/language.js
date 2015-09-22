/**
 * Pimcore
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2009-2015 pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GNU General Public License version 3 (GPLv3)
 */

pimcore.registerNS("pimcore.settings.user.workspace.language");

pimcore.settings.user.workspace.language = Class.create({

    initialize: function (type, data) {
        this.type = type;
        this.data = data;
    },

    getLayout: function() {
        var storeData = [];
        var nrOfLanguages = pimcore.settings.websiteLanguages.length;
        for (var i = 0; i < nrOfLanguages; i++) {
            var language = pimcore.settings.websiteLanguages[i];
            storeData.push([language, pimcore.available_languages[language]]);
        }

        var store = Ext.create('Ext.data.ArrayStore', {
            fields: ['id', 'text'],
            data: storeData
        });


        var options = {
            name: "languages",
            triggerAction: "all",
            editable: false,
            store: store,
            valueField: "id",
            hideLabel: true,
            width: 350,
            height: 480,
            value: this.data

        };

        this.box = new Ext.ux.form.MultiSelect(options);

        this.window = new Ext.Panel({
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