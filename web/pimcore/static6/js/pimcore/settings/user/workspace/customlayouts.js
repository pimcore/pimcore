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

pimcore.registerNS("pimcore.settings.user.workspace.customlayouts");

pimcore.settings.user.workspace.customlayouts = Class.create({

    initialize: function (type, data, allLayouts) {
        this.type = type;
        this.data = data;
        this.allLayouts = allLayouts;

    },

    getLayout: function() {

        var storeData = [];
        var i;
        for (i = 0; i < this.allLayouts.length; i++) {
            var name = this.allLayouts[i].name;
            if (this.allLayouts[i].type == "master") {
                name = "<b>" + name + "</b>";
            } else {
                name = "&nbsp;&nbsp;&nbsp;" + name;
            }
            storeData.push([this.allLayouts[i].id, name]);
        }

        var store = Ext.create('Ext.data.ArrayStore', {
            fields: ['id', 'text'],
            data: storeData
        });


        var options = {
            triggerAction: "all",
            editable: false,
            store: store,
            valueField: "id",
            //displayField: "name",
            hideLabel: true,
            width: 330,
            height: 470,
            value: this.data
        };

        this.box = new Ext.ux.form.MultiSelect(options);

        this.window = new Ext.Panel({
            bodyStyle: "padding: 10px;",
            items: [this.box],
            autoScroll: true
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