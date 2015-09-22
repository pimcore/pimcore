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

pimcore.registerNS("pimcore.settings.user.workspace.customlayouts");

pimcore.settings.user.workspace.customlayouts = Class.create({

    initialize: function (type, data, allLayouts) {
        this.type = type;
        this.data = data;
        this.allLayouts = allLayouts;

    },

    getLayout: function() {
        var store = [];
        var i;
        for (i = 0; i < this.allLayouts.length; i++) {
            var name = this.allLayouts[i].name;
            if (this.allLayouts[i].type == "master") {
                name = "<b>" + name + "</b>";
            } else {
                name = "&nbsp;&nbsp;&nbsp;" + name;
            }
            store.push([this.allLayouts[i].id, name]);
        }

        var options = {
            triggerAction: "all",
            editable: false,
            store: store,
            valueField: "id",
            displayField: "name",
            hideLabel: true,
            width: 330,
            height: 470,
            value: this.data
        };

        this.box = new Ext.ux.form.MultiSelect(options);

        this.window = new Ext.Panel({
            xtype: "form",
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