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

pimcore.registerNS("pimcore.object.bulkbase");
pimcore.object.bulkbase = Class.create({

    getTypeRenderer: function (value, metaData, record, rowIndex, colIndex, store) {
        return '<div class="pimcore_icon_' + value + '" style="min-height: 16px;" name="' + record.data.name + '">&nbsp;</div>';
    },

    getPrio: function(data) {
        switch (data.type) {
            case "fieldcollection":
                return 0;
            case "class":
                return 1;
            case "customlayout":
                return 2;
            case "objectbrick":
                return 3;
        }
        return 0;
    },

    selectAll: function(value) {
        var store = this.gridPanel.getStore();
        var records = store.getRange();
        for (var i = 0; i < records.length; i++) {
            var currentData = records[i];
            currentData.set("checked", value);
        }
    },

    sortValues: function() {
        this.values.sort(function(data1, data2){
            var value1 = this.getPrio(data1);
            var value2 = this.getPrio(data2);

            if (value1 > value2) {
                return 1;
            } else if (value1 < value2) {
                return -1;
            } else {
                return 0;
            }
        }.bind(this));
    }
});